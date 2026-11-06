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

namespace block_afirst_assistant\local;

/**
 * Tests for scorm_text_extractor.
 *
 * @package    block_afirst_assistant
 * @category   test
 * @copyright  2026 Africa CDC
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\PHPUnit\Framework\Attributes\CoversClass(\block_afirst_assistant\local\scorm_text_extractor::class)]
final class scorm_text_extractor_test extends \advanced_testcase {

    /**
     * Write a zip with the given entries to a temporary file.
     *
     * @param array $entries Entry name => content.
     * @return string Path of the zip.
     */
    private function make_zip(array $entries): string {
        $path = make_request_directory() . '/package.zip';
        $zip = new \ZipArchive();
        $zip->open($path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        return $path;
    }

    /**
     * A package shaped like an Articulate Rise 360 SCORM export.
     *
     * @param array $course The course object Rise serialises.
     * @return string Path of the zip.
     */
    private function make_rise_zip(array $course): string {
        $data = base64_encode(json_encode(['course' => $course, 'labelSet' => ['labels' => ['continue' => 'CONTINUE']]]));
        $index = '<!doctype html><html><head><title>Rise course</title><style>body{color:red}</style></head><body>'
            . '<div id="app"></div><script>window.trackingId = "UA-123";</script>'
            . '<script>window.courseData = deserialize("' . $data . '");</script></body></html>';
        return $this->make_zip([
            'imsmanifest.xml' => '<?xml version="1.0"?><manifest><resources>'
                . '<resource identifier="r1" href="scormdriver/indexAPI.html">'
                . '<file href="scormdriver/indexAPI.html"/></resource></resources></manifest>',
            'scormdriver/indexAPI.html' => '<html><body>SCORM driver launch page</body></html>',
            'scormcontent/index.html' => $index,
            'scormcontent/lib/main.bundle.js' => 'function deserialize(s){return JSON.parse(atob(s));}',
        ]);
    }

    public function test_rise_package_text_in_course_order(): void {
        $course = [
            'title' => 'M1.1 Health Data Literacy',
            'description' => '<div><p>Welcome to <strong>Module 1.1</strong> &amp; enjoy.</p></div>',
            'media' => ['image' => ['key' => 'rise/courses/logo.png']],
            'lessons' => [
                [
                    'type' => 'blocks',
                    'title' => 'Introduction',
                    'items' => [
                        ['type' => 'text', 'variant' => 'heading paragraph', 'items' => [[
                            'heading' => '<p>What is data literacy?</p>',
                            'paragraph' => '<p>Data literacy is the ability to <em>read</em> data.</p>'
                                . '<p>Second&nbsp;paragraph.</p>',
                        ]]],
                        ['type' => 'image', 'variant' => 'hero', 'items' => [[
                            'media' => ['image' => ['key' => 'rise/courses/hero.png', 'originalUrl' => 'hero.png']],
                            'caption' => '', 'paragraph' => '',
                        ]]],
                        ['type' => 'list', 'variant' => 'bulleted', 'items' => [
                            ['paragraph' => '<p>Completeness</p>'],
                            ['paragraph' => '<p>Timeliness</p>'],
                        ]],
                        ['type' => 'divider', 'family' => 'continue', 'items' => [['title' => 'CONTINUE']]],
                    ],
                ],
                [
                    'type' => 'blocks',
                    'title' => 'Checks',
                    'items' => [
                        ['type' => 'interactive', 'variant' => 'accordion', 'items' => [
                            ['title' => 'Accuracy', 'description' => '<p>Close to the truth.</p>', 'media' => []],
                            ['title' => 'Validity', 'description' => '<p>Fits the rules.</p>'],
                        ]],
                        ['type' => 'interactive', 'family' => 'flashcard', 'items' => [[
                            'back' => ['description' => '<p>Back side text</p>', 'media' => ['image' => ['key' => 'b.png']]],
                            'front' => ['type' => 'image', 'description' => 'Front of card 1'],
                        ]]],
                        ['type' => 'knowledgeCheck', 'items' => [[
                            'type' => 'MULTIPLE_CHOICE',
                            'title' => '<p>Which dimension is about delay?</p>',
                            'answers' => [
                                ['title' => '<p>Timeliness</p>', 'correct' => true],
                                ['title' => '<p>Accuracy</p>', 'correct' => false],
                            ],
                            'feedback' => '<p>Timeliness measures delay.</p>',
                        ]]],
                        ['type' => 'knowledgeCheck', 'items' => [[
                            'type' => 'MATCHING',
                            'title' => '<p>Match them</p>',
                            'answers' => [['title' => 'Accuracy', 'matchTitle' => 'Close to the truth']],
                        ]]],
                        ['type' => 'text', 'variant' => 'table', 'items' => [[
                            'paragraph' => '<table><tr><th><p>Patient</p></th><th><p>Age</p></th></tr>'
                                . '<tr><td><p>P001</p></td><td><p>45</p></td></tr></table>',
                        ]]],
                        ['type' => 'multimedia', 'variant' => 'code', 'items' => [[
                            'code' => "import pandas as pd\ndf = pd.read_csv(&quot;bp.csv&quot;)",
                        ]]],
                        ['type' => 'custom', 'family' => 'mondrian', 'blockumentId' => 'b1', 'items' => []],
                    ],
                ],
            ],
            'mondrian' => [
                'blockuments' => ['b1' => ['children' => [['id' => 'g1']]]],
                'items' => [
                    'g1' => ['type' => 'group', 'states' => ['default' => ['x' => 0, 'y' => 0, 'width' => 600]],
                        'children' => [['id' => 'right'], ['id' => 'leftbody'], ['id' => 'lefthead']]],
                    'lefthead' => ['type' => 'text', 'states' => ['default' => ['x' => 10, 'y' => 0, 'width' => 200,
                        'text' => ['json' => $this->tiptap('Myth')]]]],
                    'leftbody' => ['type' => 'text', 'states' => ['default' => ['x' => 0, 'y' => 40, 'width' => 250,
                        'text' => ['json' => $this->tiptap('Machines replace analysts.')]]]],
                    'right' => ['type' => 'text', 'states' => ['default' => ['x' => 300, 'y' => 0, 'width' => 250,
                        'text' => ['json' => $this->tiptap('Fact: analysts review the output.')]]]],
                ],
            ],
        ];

        $text = scorm_text_extractor::from_zip($this->make_rise_zip($course));

        $this->assertSame(implode("\n\n", [
            'M1.1 Health Data Literacy',
            'Welcome to Module 1.1 & enjoy.',
            'Introduction',
            "What is data literacy?\nData literacy is the ability to read data.\nSecond paragraph.",
            "Completeness\nTimeliness",
            'Checks',
            "Accuracy\nClose to the truth.\nValidity\nFits the rules.",
            'Back side text',
            "Which dimension is about delay?\nTimeliness\nAccuracy\nTimeliness measures delay.",
            "Match them\nAccuracy: Close to the truth",
            "Patient | Age\nP001 | 45",
            "import pandas as pd\ndf = pd.read_csv(\"bp.csv\")",
            "Myth\nMachines replace analysts.\nFact: analysts review the output.",
        ]), $text);
        $this->assertStringNotContainsString('UA-123', $text);
        $this->assertStringNotContainsString('CONTINUE', $text);
        $this->assertStringNotContainsString('.png', $text);
        $this->assertStringNotContainsString('SCORM driver', $text);
    }

    public function test_plain_html_package_follows_manifest_and_skips_scripts(): void {
        $manifest = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<manifest identifier="m" xmlns="http://www.imsproject.org/xsd/imscp_rootv1p1p2"
        xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_rootv1p2">
  <organizations default="o">
    <organization identifier="o">
      <title>Course</title>
      <item identifier="i1" identifierref="second"><title>Two</title></item>
      <item identifier="i2" identifierref="first"><title>One</title></item>
    </organization>
  </organizations>
  <resources>
    <resource identifier="first" type="webcontent" adlcp:scormtype="sco" href="pages/one.html">
      <file href="pages/one.html"/>
      <file href="pages/one.js"/>
      <file href="pages/one.css"/>
    </resource>
    <resource identifier="second" type="webcontent" adlcp:scormtype="sco" href="pages/two.html?start=1">
      <file href="pages/two.html"/>
      <file href="pages/three.htm"/>
    </resource>
  </resources>
</manifest>
XML;
        $text = scorm_text_extractor::from_zip($this->make_zip([
            'imsmanifest.xml' => $manifest,
            'pages/one.html' => '<html><head><title>Page one</title><script>var secret = "tracking";</script></head>'
                . '<body><h1>First page</h1><p>Body of <b>page</b> one.</p><style>p{}</style></body></html>',
            'pages/two.html' => '<html><body><p>Second page &ndash; launched first.</p>'
                . '<script type="text/javascript">alert("no");</script><!-- hidden comment --></body></html>',
            'pages/three.htm' => '<html><body><ul><li>Item A</li><li>Item B</li></ul></body></html>',
            'pages/one.js' => 'console.log("not text");',
            'pages/unlisted.html' => '<html><body>Never listed</body></html>',
        ]));

        $this->assertSame(implode("\n\n", [
            "Second page \u{2013} launched first.",
            "Item A\nItem B",
            'Page one',
            "First page\nBody of page one.",
        ]), $text);
        $this->assertStringNotContainsString('tracking', $text);
        $this->assertStringNotContainsString('alert', $text);
        $this->assertStringNotContainsString('Never listed', $text);
    }

    public function test_html_to_plain_strips_markup_and_decodes_entities(): void {
        $html = '<div data-editor-id="x"><p><span style="font-size: 1.7rem">Fish &amp; chips&#39; <strong>price</strong></span></p>'
            . '<ul><li><p>one</p></li><li>two<br>three</li></ul><script>evil()</script></div>';
        $this->assertSame("Fish & chips' price\none\ntwo\nthree", scorm_text_extractor::html_to_plain($html));
    }

    public function test_text_is_capped(): void {
        $big = str_repeat('word ', 300000);
        $course = ['title' => 'Big', 'lessons' => []];
        for ($i = 0; $i < 3; $i++) {
            $course['lessons'][] = ['title' => "Lesson $i", 'items' => [['type' => 'text', 'items' => [['paragraph' => $big]]]]];
        }
        $text = scorm_text_extractor::from_zip($this->make_rise_zip($course));
        $this->assertLessThanOrEqual(scorm_text_extractor::MAX_TEXT_BYTES, strlen($text));
        $this->assertStringStartsWith("Big\n\nLesson 0\n\nword word", $text);
    }

    public function test_from_cm_reads_the_package_of_a_scorm_activity(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        // The generator's default package is a plain-HTML SCORM 1.2 course about golf.
        $scorm = $this->getDataGenerator()->create_module('scorm', ['course' => $course->id]);

        $text = scorm_text_extractor::from_cm(get_fast_modinfo($course)->get_cm($scorm->cmid));

        $this->assertStringContainsString('Golf carts', $text);
        $this->assertStringNotContainsString('@import', $text);
    }

    public function test_unreadable_package_throws(): void {
        $path = make_request_directory() . '/broken.zip';
        file_put_contents($path, 'not a zip');
        $this->expectException(\moodle_exception::class);
        scorm_text_extractor::from_zip($path);
    }

    /**
     * A one-paragraph TipTap document.
     *
     * @param string $text
     * @return array
     */
    private function tiptap(string $text): array {
        return ['type' => 'doc', 'content' => [['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => $text]]]]];
    }
}
