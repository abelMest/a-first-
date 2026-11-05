"""Simulate grounding thresholds from one audit run made with a near-zero threshold.

An item whose best retrieval score is below T would have been refused without a model call; above T the
recorded model outcome stands. (Approximation: at a higher T fewer passages reach the model.)
"""
import csv
import sys
from collections import Counter

rows = list(csv.DictReader(open(sys.argv[1], encoding="utf-8")))
inscope = [r for r in rows if r["type"] != "out_of_scope"]
oos = [r for r in rows if r["type"] == "out_of_scope"]


def score(r):
    return float(r["top_score"] or 0)


print(f"items {len(rows)}: in scope {len(inscope)}, out of scope {len(oos)}")
print("status (in scope):", dict(Counter(r["status"] for r in inscope)))
print("status (out of scope):", dict(Counter(r["status"] for r in oos)))
print("refusal reasons (all):", dict(Counter(r["refusal_reason"] for r in rows if r["refusal_reason"])))
print("in-scope answered with expected anchor in cited passages:",
      sum(1 for r in inscope if r["status"] == "answered" and r["anchor_in_citations"] in ("1", "yes", "true")))
lat = sorted(int(r["latency_ms"]) for r in rows if r["latency_ms"])
print(f"latency ms: median {lat[len(lat)//2]}, p90 {lat[int(len(lat)*0.9)]}, max {lat[-1]}")
print("top_score in scope:  min %.2f median %.2f" % (min(map(score, inscope)), sorted(map(score, inscope))[len(inscope)//2]))
print("top_score out scope: max %.2f median %.2f" % (max(map(score, oos)), sorted(map(score, oos))[len(oos)//2]))
print()
print("threshold | in-scope answered | anchored | in-scope refused | out-of-scope answered")
for t in [x / 2 for x in range(0, 25)]:
    ans = [r for r in inscope if score(r) >= t and r["status"] == "answered"]
    anch = [r for r in ans if r["anchor_in_citations"] in ("1", "yes", "true")]
    oosans = [r for r in oos if score(r) >= t and r["status"] == "answered"]
    print(f"{t:9.1f} | {len(ans):17d} | {len(anch):8d} | {len(inscope) - len(ans):16d} | {len(oosans):21d}")
