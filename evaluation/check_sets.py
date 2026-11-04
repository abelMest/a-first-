"""Validate the A-FIRST evaluation CSVs. Run: python -I check_sets.py <eval_dir> <course_text_dir>"""
import csv, sys, os, re, glob, collections

EV, TXT = sys.argv[1], sys.argv[2]

def norm(s):
    s = s.replace("’", "'").replace("‘", "'").replace("“", '"').replace("”", '"')
    return re.sub(r"\s+", " ", s).strip().lower()

texts = {}
for p in glob.glob(os.path.join(TXT, "topic_*_clean.txt")):
    mod = re.search(r"topic_(\d)_(\d)", p).group(0)[6:].replace("_", ".")
    texts[mod] = norm(open(p, encoding="utf-8").read())
print("course modules loaded:", sorted(texts))

def check(path, idcol, srccol, modcol):
    rows = list(csv.DictReader(open(path, encoding="utf-8", newline="")))
    widths = {len(r) for r in rows}
    misses = []
    for r in rows:
        src = r[srccol]
        if src == "NONE":
            continue
        m = re.match(r'Module (\d\.\d): "(.+)"$', src)
        if not m or m.group(1) != r[modcol]:
            misses.append((r[idcol], "bad expected_source format/module", src)); continue
        if norm(m.group(2)) not in texts[m.group(1)]:
            other = [k for k, t in texts.items() if norm(m.group(2)) in t]
            misses.append((r[idcol], f"anchor not found in {m.group(1)} (found in {other})", m.group(2)))
    return rows, widths, misses

rows, widths, misses = check(os.path.join(EV, "audit_set.csv"), "id", "expected_source", "module")
print("\naudit_set.csv rows:", len(rows), "| field counts:", widths, "| unique ids:", len({r['id'] for r in rows}))
print("types:", dict(collections.Counter(r["type"] for r in rows)))
print("per module (in scope):", dict(sorted(collections.Counter(r["module"] for r in rows if r["type"] != "out_of_scope").items())))
print("multi-step per module:", dict(sorted(collections.Counter(r["module"] for r in rows if r["type"] == "multi-step").items())))
print("competencies covered:", len({r['competency_id'] for r in rows if r['competency_id'] != 'none'}))
print("anchor misses:", misses or "none")

rows, widths, misses = check(os.path.join(EV, "bias_audit_sample.csv"), "id", "expected_source", "module")
print("\nbias_audit_sample.csv rows:", len(rows), "| field counts:", widths)
print("language x topic:")
for (l, t), c in sorted(collections.Counter((r["language"], r["topic_area"]) for r in rows).items()):
    print(f"  {l:11s} {t}: {c}")
print("anchor misses:", misses or "none")
