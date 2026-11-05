"""Measure how well each rehearsal answer is supported by the passages it cited."""
import csv
import re
import sys

csv.field_size_limit(10**8)
STOP = set("""a an the is it in on at to for of and or with this are be as by from not each their than into about used
if have has been will may would can also such these which more some they you we its one that was were but so do does
did when where how what who all any both few most other same then too very just because while although however select
learn image chapter module click screen card cards slide tab tabs template block""".split())


def stem(w):
    for suf, minlen in (("ing", 6), ("ed", 5), ("es", 5), ("s", 4)):
        if w.endswith(suf) and len(w) >= minlen:
            return w[: -len(suf)]
    return w


def terms(text):
    words = re.findall(r"[^\W_]{3,}", text.lower())
    return [stem(w) for w in words if w not in STOP and not w.isdigit()]


def numbers(text):
    return {n.replace(",", "") for n in re.findall(r"\d+(?:[.,]\d+)?", text)}


chunks = {r["id"]: r["content"] for r in csv.DictReader(open(sys.argv[2], encoding="utf-8"))}
rows = list(csv.DictReader(open(sys.argv[1], encoding="utf-8")))
out = []
for r in rows:
    if r["status"] != "answered":
        continue
    passages = " ".join(chunks.get(i, "") for i in r["cited_passages"].split(";") if i)
    answer = re.sub(r"\[\d+\]", " ", r["answer"])
    at, pt = terms(answer), set(terms(passages))
    cover = sum(1 for t in at if t in pt) / len(at) if at else 0
    missingnum = sorted(numbers(answer) - numbers(passages))
    unknown = sorted({t for t in at if t not in pt})
    kind = "OOS" if r["type"] == "out_of_scope" else ("in+anch" if r["anchor_in_citations"] in ("1", "yes", "true") else "in-noanch")
    out.append((cover, kind, r["item_id"], missingnum, unknown[:8]))

for cover, kind, item, miss, unk in sorted(out):
    print(f"{cover:5.2f} {kind:9} {item} nums_missing={miss} unknown={unk}")
