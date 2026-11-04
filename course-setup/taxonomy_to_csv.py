#!/usr/bin/env python3
"""Turn a course taxonomy.json into a Moodle 5.0 tool_lpimportcsv CSV.

Output (next to this script unless --out-dir is given):
  competency_framework_A-FIRST-DS4H-V1.csv   import via Site admin > Competencies >
                                             Import competency framework
  competency_prerequisites.json              directed prerequisite edges, kept apart
                                             because Moodle "cross-referenced"
                                             (related) competencies are symmetric

Column layout checked against MOODLE_500_STABLE admin/tool/lpimportcsv:
  framework_importer::read_mapping_data() default indexes 0..13 and the
  lang strings in lang/en/tool_lpimportcsv.php (used as the header row).

Usage:
  python taxonomy_to_csv.py [--taxonomy PATH] [--out-dir DIR]
"""
import argparse
import csv
import html
import json
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent
COURSES = HERE.parent / "courses"

# Exact header strings, in the importer's default index order.
HEADERS = [
    "Parent ID number",                         # 0  parentidnumber
    "ID number",                                # 1  idnumber
    "Short name",                               # 2  shortname
    "Description",                              # 3  description
    "Description format",                       # 4  descriptionformat
    "Scale values",                             # 5  scalevalues
    "Scale configuration",                      # 6  scaleconfiguration
    "Rule type (optional)",                     # 7  ruletype
    "Rule outcome (optional)",                  # 8  ruleoutcome
    "Rule config (optional)",                   # 9  ruleconfig
    "Cross-referenced competency ID numbers",   # 10 relatedidnumbers
    "Exported ID (optional)",                   # 11 exportid
    "Is framework",                             # 12 isframework
    "Taxonomy",                                 # 13 taxonomies
]

FORMAT_HTML = "1"
# Moodle's default competence scale ("Not yet competent,Competent").
# The importer matches these items against existing scales and only
# creates a new scale if none matches; it then fills in scaleid itself.
SCALE_VALUES = "Not yet competent,Competent"
SCALE_CONFIG = json.dumps([
    {"scaleid": ""},
    {"id": 1, "scaledefault": 1, "proficient": 0},
    {"id": 2, "scaledefault": 0, "proficient": 1},
], separators=(",", ":"))
# One taxonomy term per framework level: modules, then competencies.
TAXONOMIES = "domain,competency"
SHORTNAME_MAX = 100  # importer applies shorten_text(..., 100)


def module_idnumber(module_id):
    return f"MOD-{module_id}"


def build_rows(tax):
    rows = []
    framework_id = tax["framework_id"]
    rows.append({
        "Parent ID number": "",
        "ID number": framework_id,
        "Short name": tax["title"][:SHORTNAME_MAX],
        "Description": html.escape(
            f"{tax['title']} (v{tax['version']}) for course {tax['course_id']}: "
            f"{tax['course_name']}. Prerequisite links are kept in "
            f"competency_prerequisites.json, not as cross-references."),
        "Description format": FORMAT_HTML,
        "Scale values": SCALE_VALUES,
        "Scale configuration": SCALE_CONFIG,
        "Is framework": "1",
        "Taxonomy": TAXONOMIES,
    })
    for module in tax["modules"]:
        rows.append({
            "Parent ID number": "",
            "ID number": module_idnumber(module["id"]),
            "Short name": f"Module {module['id']}: {module['title']}"[:SHORTNAME_MAX],
            "Description": html.escape(f"Module {module['id']} of {tax['course_name']}."),
            "Description format": FORMAT_HTML,
        })
        for comp in module["competencies"]:
            rows.append({
                "Parent ID number": module_idnumber(module["id"]),
                "ID number": comp["id"],
                "Short name": comp["title"][:SHORTNAME_MAX],
                "Description": html.escape(comp["learning_objective"]),
                "Description format": FORMAT_HTML,
            })
    return [[row.get(h, "") for h in HEADERS] for row in rows]


def build_prerequisites(tax):
    known = {c["id"] for m in tax["modules"] for c in m["competencies"]}
    edges = []
    for module in tax["modules"]:
        for comp in module["competencies"]:
            for pre in comp["prerequisites"]:
                if pre not in known:
                    raise ValueError(f"{comp['id']} lists unknown prerequisite {pre}")
                edges.append({"competency": comp["id"], "requires": pre})
    return {
        "framework_idnumber": tax["framework_id"],
        "note": ("Directed prerequisite edges (competency requires prerequisite). "
                 "Not imported as Moodle cross-references, which are symmetric. "
                 "Use for learning-plan ordering and documentation."),
        "edges": edges,
    }


def validate(tax, rows):
    errors = []
    n_modules = len(tax["modules"])
    n_comps = sum(len(m["competencies"]) for m in tax["modules"])
    if n_modules != tax.get("total_modules", n_modules):
        errors.append(f"modules: found {n_modules}, taxonomy says {tax['total_modules']}")
    if n_comps != tax.get("total_competencies", n_comps):
        errors.append(f"competencies: found {n_comps}, taxonomy says {tax['total_competencies']}")
    ids = [r[1] for r in rows]
    dupes = {i for i in ids if ids.count(i) > 1}
    if dupes:
        errors.append(f"duplicate ID numbers: {sorted(dupes)}")
    parents = set(ids[1:])
    for r in rows[1:]:
        if r[0] and r[0] not in parents:
            errors.append(f"{r[1]}: parent {r[0]} not in file")
    return n_modules, n_comps, errors


def main(argv=None):
    ap = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    ap.add_argument("--course", default="ds4h", help="folder under courses/ (default: ds4h)")
    ap.add_argument("--taxonomy", type=Path, help="default: courses/<course>/taxonomy.json")
    ap.add_argument("--out-dir", type=Path, help="default: courses/<course>/")
    args = ap.parse_args(argv)
    args.taxonomy = args.taxonomy or COURSES / args.course / "taxonomy.json"
    args.out_dir = args.out_dir or COURSES / args.course

    tax = json.loads(args.taxonomy.read_text(encoding="utf-8"))
    rows = build_rows(tax)
    n_modules, n_comps, errors = validate(tax, rows)
    if errors:
        for e in errors:
            print("ERROR:", e, file=sys.stderr)
        return 1

    args.out_dir.mkdir(parents=True, exist_ok=True)
    csv_path = args.out_dir / f"competency_framework_{tax['framework_id']}.csv"
    # UTF-8 without BOM, comma separator: the import form's defaults.
    with csv_path.open("w", encoding="utf-8", newline="") as f:
        writer = csv.writer(f, quoting=csv.QUOTE_MINIMAL, lineterminator="\n")
        writer.writerow(HEADERS)
        writer.writerows(rows)

    pre_path = args.out_dir / "competency_prerequisites.json"
    prereqs = build_prerequisites(tax)
    pre_path.write_text(json.dumps(prereqs, indent=2) + "\n", encoding="utf-8")

    print(f"Wrote {csv_path.name}: framework {tax['framework_id']}, "
          f"{n_modules} module parents, {n_comps} child competencies "
          f"({len(rows)} data rows).")
    print(f"Wrote {pre_path.name}: {len(prereqs['edges'])} prerequisite edges.")
    return 0


if __name__ == "__main__":
    sys.exit(main())
