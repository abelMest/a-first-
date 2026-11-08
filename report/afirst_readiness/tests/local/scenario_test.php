<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace report_afirst_readiness\local;

/**
 * Scenario validation, path scoring and readiness rules.
 *
 * @package    report_afirst_readiness
 * @category   test
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\report_afirst_readiness\local\scenario::class)]
#[\PHPUnit\Framework\Attributes\CoversClass(\report_afirst_readiness\local\rules::class)]
final class scenario_test extends \basic_testcase {

    /**
     * A small valid scenario with a branch (tests/fixtures/tiny_scenario.json).
     *
     * @return array
     */
    private static function tiny(): array {
        return json_decode(file_get_contents(__DIR__ . '/../fixtures/tiny_scenario.json'), true);
    }

    public function test_pilot_scenario_file_is_valid(): void {
        $path = __DIR__ . '/../../../../courses/ds4h/readiness/scenarios/DS4H-RDY-01.json';
        $taxonomy = __DIR__ . '/../../../../courses/ds4h/taxonomy.json';
        if (!is_file($path)) {
            $this->markTestSkipped('Course data is only present in the A-FIRST repository checkout.');
        }
        $competencies = [];
        foreach (json_decode(file_get_contents($taxonomy), true)['modules'] as $m) {
            $competencies = array_merge($competencies, array_column($m['competencies'], 'id'));
        }
        $this->assertSame([], scenario::validate(json_decode(file_get_contents($path), true), $competencies));
    }

    public function test_validation_errors(): void {
        $this->assertSame([], scenario::validate(self::tiny()));

        $s = self::tiny();
        $s['nodes'][2]['options'][0]['next'] = 'A';
        $this->assertContains('cycle through node A', scenario::validate($s));

        $s = self::tiny();
        $s['nodes'][0]['options'][1]['next'] = 'C';
        $this->assertContains('node B cannot be reached from the start', scenario::validate($s));

        $s = self::tiny();
        $s['nodes'][0]['options'][0]['scores'] = ['Z' => 9];
        $errors = scenario::validate($s);
        $this->assertContains('node A option 1 scores unknown domain Z', $errors);

        $s = self::tiny();
        $s['domains'][0]['competencies'] = ['COMP-9.9.9'];
        $this->assertContains('domain X lists unknown competency COMP-9.9.9', scenario::validate($s, ['COMP-1.1.1']));

        $this->assertSame(['scenario is not a JSON object'], scenario::validate(null));
    }

    public function test_score_follows_the_path_taken(): void {
        // Best path: A1 -> C1.
        $best = scenario::score(self::tiny(), [['A', '1'], ['C', '1']]);
        $this->assertTrue($best['completed']);
        $this->assertFalse($best['critical']);
        $this->assertEquals(['X' => 100.0, 'Y' => 100.0], $best['domains']);

        // Branch: A2 (0/3) -> B1 (2/2) -> C2 (critical, 0/3).
        $branch = scenario::score(self::tiny(), [['A', '2'], ['B', '1'], ['C', '2']]);
        $this->assertSame(['A', 'B', 'C'], $branch['path']);
        $this->assertEquals(40.0, $branch['domains']['X']);
        $this->assertEquals(0.0, $branch['domains']['Y']);
        $this->assertTrue($branch['critical']);

        $partial = scenario::score(self::tiny(), [['A', '1']]);
        $this->assertFalse($partial['completed']);
        $this->assertNull($partial['domains']['Y']);
    }

    public function test_rules(): void {
        $base = ['started' => true, 'completed' => true, 'critical' => false, 'domains' => ['X' => 90, 'Y' => 85],
            'knowledge' => ['1.1' => 90, '1.2' => 80], 'supervisorconfigured' => false, 'supervisor' => null];

        $this->assertSame(rules::NOT_STARTED, rules::classify(['started' => false] + $base)['status']);
        $this->assertSame(rules::IN_PROGRESS, rules::classify(['completed' => false] + $base)['status']);
        $this->assertSame(rules::INDEPENDENT, rules::classify($base)['status']);

        // A critical choice caps the result at supervised, and says why.
        $r = rules::classify(['critical' => true] + $base);
        $this->assertSame(rules::SUPERVISED, $r['status']);
        $this->assertSame([['critical', null]], $r['reasons']);

        // Exactly at the threshold passes; just below does not.
        $this->assertSame(rules::INDEPENDENT, rules::classify(['domains' => ['X' => 80, 'Y' => 80]] + $base)['status']);
        $r = rules::classify(['domains' => ['X' => 79.9, 'Y' => 80]] + $base);
        $this->assertSame(rules::SUPERVISED, $r['status']);
        $this->assertSame('domain', $r['reasons'][0][0]);

        $r = rules::classify(['domains' => ['X' => 59, 'Y' => 90]] + $base);
        $this->assertSame(rules::NOT_YET, $r['status']);

        // Below the supervised bar, a critical choice is still named first.
        $r = rules::classify(['critical' => true, 'domains' => ['X' => 50, 'Y' => 90]] + $base);
        $this->assertSame(rules::NOT_YET, $r['status']);
        $this->assertSame(['critical', null], $r['reasons'][0]);
        $this->assertSame('domain', $r['reasons'][1][0]);

        // A knowledge check not taken blocks independence and counts as 0 in the mean.
        $r = rules::classify(['knowledge' => ['1.1' => 100, '1.2' => null]] + $base);
        $this->assertSame(rules::NOT_YET, $r['status']);
        $this->assertSame('knowledgemeanmissing', $r['reasons'][0][0]);
        $this->assertSame(1, $r['reasons'][0][1]['missing']);
        $r = rules::classify(['knowledge' => ['1.1' => 50, '1.2' => 50]] + $base);
        $this->assertSame('knowledgemean', $r['reasons'][0][0]);

        // Supervisor rating, when configured, is required for independence.
        $sup = ['supervisorconfigured' => true] + $base;
        $this->assertSame(rules::SUPERVISED, rules::classify(['supervisor' => null] + $sup)['status']);
        $this->assertSame(rules::INDEPENDENT, rules::classify(['supervisor' => 70] + $sup)['status']);

        // No knowledge checks configured: the knowledge criteria are skipped.
        $this->assertSame(rules::INDEPENDENT, rules::classify(['knowledge' => []] + $base)['status']);

        // Course thresholds override the defaults.
        $this->assertSame(rules::SUPERVISED, rules::classify($base, ['independent_domain_min' => 95])['status']);
    }
}
