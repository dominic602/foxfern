# Housecall Pro Labor Rates

- `foxfern_housecall_pricebook.csv`: 10 labor-rate lines to import into Housecall Pro (5 residential, 4 commercial, Net 30). Products are uploaded separately.
- `foxfern_pricing_calculator.xlsx`: editable sub cost / PM / sell rate per line with GP check, plus a job estimator (hours per line + material cost).
- `build_pricebook.py`: change the rates at the top and run `python3 build_pricebook.py` to rebuild both files. It stops if any line is below 33% gross profit.
