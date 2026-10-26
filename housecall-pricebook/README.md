# Housecall Pro Price Book

- `foxfern_housecall_pricebook.csv`: import into Housecall Pro (Price Book > Services > Import). 198 items, residential (`RES -`) and commercial (`COM -`) categories.
- `foxfern_pricing_calculator.xlsx`: editable rate inputs, a quick job estimator (labor days/hours, material $, sub $, Net 30) and the full price book with GP %.
- `build_pricebook.py`: change the rates at the top, run `python3 build_pricebook.py`, and both files are rebuilt. It stops with an error if any item falls below 33% gross profit.

If the header row in your Housecall Pro export template is different, edit `HEADER` in the script to match it.
