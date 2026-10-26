"""Build the Fox & Fern Housecall Pro price book (CSV) and the pricing calculator (XLSX).

Edit the RATE INPUTS below, run `python3 build_pricebook.py`, and re-import the CSV.

Pricing model
  Labor cost/hr  = sub crew rate + PM allocation (PM loaded hourly x PM hours per field hour)
  Labor price/hr = rounded labor cost / (1 - target labor margin)
  Materials      = cost x material markup (1.5x residential, 2.0x commercial)
  Sub quotes     = (sub quote + PM allocation %) x sub multiplier
Every item is checked for >= 33% gross profit and flagged when material cost > 20% of price.
"""
import csv
import math
from pathlib import Path

from openpyxl import Workbook
from openpyxl.styles import Alignment, Font, PatternFill
from openpyxl.workbook.defined_name import DefinedName

OUT = Path(__file__).parent

# ---------------------------------------------------------------- RATE INPUTS
PM_WEEKLY_PAY = 1000.00   # PM gross pay per week
PM_BURDEN = 0.25          # payroll taxes, workers comp, etc.
PM_WEEKLY_HOURS = 40
MIN_GP = 0.33             # hard floor, every line is checked against it
MAX_MATERIAL_PCT = 0.20   # material cost as % of selling price (flag only)

SEGMENTS = {
    "RES": dict(
        label="Residential",
        sub_rate=60.00,       # blended remodel sub, per crew-hour (~$480/day)
        pm_ratio=0.25,        # PM hours per field hour
        labor_price=115.00,   # sell rate per labor hour
        mat_markup=1.50,
        sub_pm_pct=0.05,      # PM time added on top of sub quotes
        sub_multiplier=1.65,
    ),
    "COM": dict(
        label="Commercial (Montgomery Co. & Philadelphia)",
        sub_rate=65.00,       # commercial-insured sub (COI, additional insured)
        pm_ratio=0.30,
        labor_price=150.00,
        mat_markup=2.00,
        sub_pm_pct=0.08,
        sub_multiplier=1.90,
    ),
}

PM_HOURLY = PM_WEEKLY_PAY * (1 + PM_BURDEN) / PM_WEEKLY_HOURS
for s in SEGMENTS.values():
    s["labor_cost"] = s["sub_rate"] + PM_HOURLY * s["pm_ratio"]

# ---------------------------------------------------------------- ITEMS
# kind: "L" = labor hours + consumable materials, "S" = sub quote per unit,
#       "F" = fixed price/cost (fees, pass-throughs), keyed per segment
# (category, name, unit, kind, labor_hrs_or_sub_cost, material_cost, description)
L, S = "L", "S"
ITEMS = [
    # Labor & rates -- the core estimating lines
    ("Labor & Rates", "Remodel Labor - Per Hour", "Hour", L, 1, 0,
     "Skilled remodel labor, per labor hour. Includes project management and supervision. Qty = total labor hours."),
    ("Labor & Rates", "Remodel Labor - Crew Day (1 tech, 8 hrs)", "Day", L, 8, 0,
     "One technician, 8-hour day. Includes project management. Qty = total labor days."),
    ("Labor & Rates", "Remodel Labor - 2-Person Crew Day (16 hrs)", "Day", L, 16, 0,
     "Two-person crew, 8-hour day (16 labor hours). Includes project management."),
    ("Labor & Rates", "Handyman - Service Call (first 2 hrs)", "Each", L, 2, 0,
     "Minimum handyman visit: trip and first 2 hours of labor. Materials billed separately."),
    ("Labor & Rates", "Handyman - Additional Hour", "Hour", L, 1, 0,
     "Each additional handyman labor hour after the first 2."),
    ("Labor & Rates", "Handyman - Half Day (4 hrs)", "Each", L, 4, 0,
     "Half-day handyman block for punch lists / honey-do lists. Materials billed separately."),
    ("Labor & Rates", "Handyman - Full Day (8 hrs)", "Each", L, 8, 0,
     "Full-day handyman block. Materials billed separately."),
    # Materials & subs -- "per $1" lines: qty = your cost in dollars
    ("Materials & Subcontractors", "Materials (per $1 of cost)", "$1", "MAT", 0, 0,
     "Project materials at standard markup. Enter qty = material cost in dollars (e.g. $1,250 of materials = qty 1250)."),
    ("Materials & Subcontractors", "Specialty Subcontractor (per $1 of sub quote)", "$1", "SUB", 0, 0,
     "Licensed specialty trades (electrical, plumbing, HVAC, countertops, glass). Qty = sub's quote in dollars. Includes coordination and PM."),
    ("Materials & Subcontractors", "Dumpster - 10 Yard", "Each", "PASS", 425, 0,
     "10-yard roll-off dumpster, delivery, pickup and disposal (small bath / single room)."),
    ("Materials & Subcontractors", "Dumpster - 20 Yard", "Each", "PASS", 550, 0,
     "20-yard roll-off dumpster, delivery, pickup and disposal (kitchen / multi-room / deck tear-off)."),
    ("Materials & Subcontractors", "Debris Haul-Away - Truckload", "Each", L, 3, 150,
     "Load, haul and dispose of one pickup/trailer load of construction debris. Includes dump fees."),
    ("Materials & Subcontractors", "Permit Fee (pass-through, per $1)", "$1", "PERMIT", 0, 0,
     "Municipal permit fees billed at cost. Qty = permit fee in dollars."),
    ("Materials & Subcontractors", "Permit Acquisition & Drawings Admin", "Each", "PMHRS", 3, 0,
     "Prepare application, drawings/sketches, submit and schedule inspections with the township or city."),
    # Demo & prep
    ("Interior Remodel - Demo & Prep", "Demo - Kitchen (standard, up to 150 sf)", "Each", L, 16, 75,
     "Remove cabinets, counters, backsplash and flooring; protect adjacent areas. Dumpster billed separately."),
    ("Interior Remodel - Demo & Prep", "Demo - Full Bathroom (up to 60 sf)", "Each", L, 10, 50,
     "Remove tub/shower, tile, vanity, toilet and flooring to studs/subfloor. Dumpster billed separately."),
    ("Interior Remodel - Demo & Prep", "Demo - Drywall/Plaster", "Sq Ft", L, 0.02, 0.02,
     "Remove drywall or plaster from walls/ceilings, bag debris. Per sf of wall/ceiling."),
    ("Interior Remodel - Demo & Prep", "Demo - Flooring (tile, carpet, LVP)", "Sq Ft", L, 0.03, 0.02,
     "Remove existing floor covering and scrape/prep subfloor."),
    ("Interior Remodel - Demo & Prep", "Dust Protection / Zip Wall Setup", "Room", L, 2, 60,
     "Plastic zip walls, floor protection and HEPA dust control per room."),
    # Drywall & framing
    ("Interior Remodel - Drywall & Framing", "Drywall - Hang, Tape & Finish (Level 4)", "Sq Ft", L, 0.025, 0.55,
     "Supply and install 1/2\" drywall, tape and finish to level 4, ready for paint. Per sf of board."),
    ("Interior Remodel - Drywall & Framing", "Drywall Patch - Small (up to 6\")", "Each", L, 1.5, 15,
     "Patch, finish and prime small hole. Includes return trip for finish coats."),
    ("Interior Remodel - Drywall & Framing", "Drywall Patch - Medium (up to 2'x2')", "Each", L, 3, 30,
     "Cut out, back-block, patch, finish and prime. Includes return trip."),
    ("Interior Remodel - Drywall & Framing", "Skim Coat Walls", "Sq Ft", L, 0.02, 0.10,
     "Skim coat damaged plaster/drywall smooth, ready for paint."),
    ("Interior Remodel - Drywall & Framing", "Interior Wall Framing (8' wall)", "Lin Ft", L, 0.5, 12,
     "Frame non-load-bearing 2x4 wall with top/bottom plates at 16\" o.c."),
    ("Interior Remodel - Drywall & Framing", "Basement Finishing - Labor & Consumables", "Sq Ft", L, 0.25, 3.00,
     "Frame, insulate, drywall, trim and doors per sf of finished floor. Electric, HVAC and flooring billed separately."),
    # Carpentry & trim
    ("Interior Remodel - Carpentry & Trim", "Baseboard - Supply & Install (primed MDF)", "Lin Ft", L, 0.06, 1.75,
     "Supply and install primed 3-1/4\" to 5-1/4\" baseboard, caulk and fill, ready for paint."),
    ("Interior Remodel - Carpentry & Trim", "Crown Molding - Supply & Install", "Lin Ft", L, 0.10, 2.50,
     "Supply and install primed crown molding, cope corners, caulk and fill."),
    ("Interior Remodel - Carpentry & Trim", "Door Casing - Per Side", "Each", L, 1, 25,
     "Supply and install primed casing on one side of a door opening."),
    ("Interior Remodel - Carpentry & Trim", "Interior Prehung Door - Install Only", "Each", L, 2.5, 20,
     "Install customer- or allowance-supplied prehung door, shim, hang hardware. Door via Materials line."),
    ("Interior Remodel - Carpentry & Trim", "Closet Shelving System - Install", "Each", L, 3, 20,
     "Install wire or laminate closet system (standard reach-in). System via Materials line."),
    ("Interior Remodel - Carpentry & Trim", "Stair Treads - Replace", "Each", L, 1.25, 10,
     "Remove and replace one stair tread and riser. Tread via Materials line."),
    # Kitchen
    ("Interior Remodel - Kitchen", "Cabinet Install - Base or Wall", "Lin Ft", L, 0.75, 4,
     "Install, level, shim and fasten cabinets; install hardware. Cabinets via Materials line."),
    ("Interior Remodel - Kitchen", "Cabinet Crown / Light Rail", "Lin Ft", L, 0.15, 1,
     "Install cabinet crown or light rail molding. Molding via Materials line."),
    ("Interior Remodel - Kitchen", "Backsplash Tile - Install", "Sq Ft", L, 0.20, 2.50,
     "Install backsplash tile incl. thinset, grout and caulk. Tile via Materials line."),
    ("Interior Remodel - Kitchen", "Countertop Template & Install Coordination", "Each", "PMHRS", 3, 0,
     "Schedule fabricator template and install, disconnect/reconnect sink. Fabricator via Specialty Sub line."),
    ("Interior Remodel - Kitchen", "Appliance Install - Each", "Each", L, 1, 15,
     "Install and connect dishwasher, range, hood, microwave or refrigerator (existing utilities)."),
    # Bathroom
    ("Interior Remodel - Bathroom", "Tub/Shower Tile Surround - Labor & Prep", "Each", L, 32, 450,
     "Standard 5' alcove: cement board, waterproof membrane, tile walls, niche, grout and seal. Tile via Materials line."),
    ("Interior Remodel - Bathroom", "Floor Tile - Install", "Sq Ft", L, 0.18, 2.25,
     "Install floor tile incl. underlayment/thinset/grout. Tile via Materials line."),
    ("Interior Remodel - Bathroom", "Vanity & Top - Install", "Each", L, 4, 25,
     "Install vanity and top; plumber connects or by Specialty Sub line. Vanity via Materials line."),
    ("Interior Remodel - Bathroom", "Toilet - Remove & Reset", "Each", L, 1.5, 20,
     "Remove and reset/replace toilet incl. wax ring and supply line."),
    ("Interior Remodel - Bathroom", "Bath Accessories Package", "Each", L, 2, 15,
     "Install towel bars, TP holder, mirror, medicine cabinet."),
    ("Interior Remodel - Bathroom", "Full Bath Remodel - Labor & PM Package (5x8)", "Each", L, 80, 900,
     "Demo, framing/backer, waterproofing, tile, vanity, toilet, trim and accessories. Plumbing/electric, fixtures and tile billed separately."),
    # Flooring
    ("Interior Remodel - Flooring", "LVP / Laminate - Install", "Sq Ft", L, 0.03, 0.35,
     "Install floating LVP or laminate incl. underlayment and transitions. Flooring via Materials line."),
    ("Interior Remodel - Flooring", "Hardwood - Nail-Down Install", "Sq Ft", L, 0.05, 0.40,
     "Install solid or engineered hardwood incl. paper and fasteners. Flooring via Materials line."),
    ("Interior Remodel - Flooring", "Floor Leveling / Self-Leveler", "Sq Ft", L, 0.02, 0.75,
     "Grind high spots and pour self-leveling compound."),
    ("Interior Remodel - Flooring", "Subfloor - Replace (per 4x8 sheet)", "Each", L, 1.5, 55,
     "Remove damaged subfloor and install 3/4\" T&G plywood, glued and screwed."),
    ("Interior Remodel - Flooring", "Shoe Molding / Quarter Round", "Lin Ft", L, 0.03, 0.75,
     "Supply and install shoe molding or quarter round after flooring."),
    # Handyman
    ("Handyman", "TV Wall Mount - Install", "Each", L, 1.5, 10,
     "Mount customer-supplied TV and bracket on drywall/studs, conceal cables where possible."),
    ("Handyman", "Light Fixture - Replace", "Each", L, 1, 10,
     "Replace existing light fixture on existing box (like-for-like)."),
    ("Handyman", "Ceiling Fan - Replace", "Each", L, 2, 15,
     "Replace existing fixture/fan on fan-rated box (like-for-like)."),
    ("Handyman", "Faucet - Replace", "Each", L, 1.5, 15,
     "Replace kitchen or bath faucet incl. supply lines."),
    ("Handyman", "Garbage Disposal - Replace", "Each", L, 1.5, 15,
     "Replace existing disposal (like-for-like)."),
    ("Handyman", "Tub / Shower Re-Caulk", "Each", L, 1.5, 15,
     "Remove old caulk, clean and re-caulk tub or shower with mildew-resistant silicone."),
    ("Handyman", "Grab Bar - Install", "Each", L, 1, 15,
     "Install ADA grab bar into blocking/studs or with rated anchors."),
    ("Handyman", "Smoke / CO Detector - Install", "Each", L, 0.25, 0,
     "Install or replace battery or hardwired detector (unit via Materials line)."),
    ("Handyman", "Door - Adjust / Plane / Rehang", "Each", L, 1, 5,
     "Adjust hinges, plane and re-hang sticking interior or exterior door."),
    ("Handyman", "Weatherstrip & Door Sweep", "Each", L, 1, 25,
     "Replace weatherstripping and door sweep on one exterior door."),
    ("Handyman", "Storm Door - Install", "Each", L, 4, 25,
     "Install customer- or allowance-supplied storm door. Door via Materials line."),
    ("Handyman", "Window / Door Screen - Rescreen", "Each", L, 1, 20,
     "Rescreen one window or door screen."),
    ("Handyman", "Shelving / Curtain Rods / Pictures (per hr)", "Hour", L, 1, 5,
     "Hang shelves, curtain rods, mirrors and pictures."),
    ("Handyman", "Furniture / Equipment Assembly (per hr)", "Hour", L, 1, 0,
     "Assemble furniture, playsets or equipment."),
    ("Handyman", "Gutter Cleaning - Single Story", "Each", L, 2.5, 10,
     "Clean gutters and flush downspouts, single-story home up to 150 LF."),
    ("Handyman", "Mailbox & Post - Replace", "Each", L, 2, 30,
     "Remove old post, set new post in concrete and mount mailbox (mailbox via Materials line)."),
    # Outdoor
    ("Outdoor Remodel - Decks", "Deck - Pressure-Treated, Built New", "Sq Ft", L, 0.30, 9.50,
     "New PT deck: footings, framing, joist tape, decking and hardware. Railing and stairs separate."),
    ("Outdoor Remodel - Decks", "Deck - Composite Decking Install (labor)", "Sq Ft", L, 0.35, 2.00,
     "Install composite decking with hidden fasteners over new or existing framing. Composite via Materials line."),
    ("Outdoor Remodel - Decks", "Deck Board Replacement", "Sq Ft", L, 0.15, 3.50,
     "Remove and replace PT deck boards, re-fasten with coated screws."),
    ("Outdoor Remodel - Decks", "Deck Railing - Pressure-Treated", "Lin Ft", L, 0.60, 18,
     "Supply and install PT railing with 2x2 balusters, code height."),
    ("Outdoor Remodel - Decks", "Deck Stairs - Per Step", "Each", L, 1.5, 40,
     "Supply and build PT stair step (stringers, tread, riser) up to 4' wide."),
    ("Outdoor Remodel - Decks", "Deck Tear-Off & Removal", "Sq Ft", L, 0.08, 0.10,
     "Demo existing deck and load debris. Dumpster billed separately."),
    ("Outdoor Remodel - Decks", "Deck Wash & Seal", "Sq Ft", L, 0.03, 0.25,
     "Pressure wash, brighten and apply penetrating stain/sealer."),
    ("Outdoor Remodel - Decks", "Pergola - PT 10x12", "Each", L, 24, 1200,
     "Build 10'x12' pressure-treated pergola on footings."),
    ("Outdoor Remodel - Fencing", "Fence - Wood Privacy 6' (supply & install)", "Lin Ft", L, 0.25, 22,
     "Supply and install 6' PT privacy fence, posts set in concrete."),
    ("Outdoor Remodel - Fencing", "Fence Post - Replace", "Each", L, 2, 45,
     "Dig out failed post and set new 4x4 PT post in concrete."),
    ("Outdoor Remodel - Fencing", "Fence Gate - Build & Hang", "Each", L, 4, 150,
     "Build and hang 4' wood gate with hardware and latch."),
    ("Outdoor Remodel - Exterior Carpentry", "Exterior Trim / Fascia Rot Repair", "Lin Ft", L, 0.30, 5,
     "Remove rotted trim/fascia and replace with PVC or primed wood, ready for paint."),
    ("Outdoor Remodel - Exterior Carpentry", "Siding Repair", "Sq Ft", L, 0.15, 3,
     "Remove and replace damaged siding sections to match as close as possible."),
    ("Outdoor Remodel - Exterior Carpentry", "Vinyl Siding - Install (per square)", "Square", L, 6, 180,
     "Install vinyl siding per 100 sf incl. house wrap, J-channel and trim. Siding via Materials line."),
    ("Outdoor Remodel - Exterior Carpentry", "Exterior Door - Install Only", "Each", L, 5, 45,
     "Install prehung exterior door, flash, insulate, trim. Door via Materials line."),
    ("Outdoor Remodel - Exterior Carpentry", "Replacement Window - Install Only", "Each", L, 2.5, 25,
     "Install insert replacement window, insulate, caulk, interior trim. Window via Materials line."),
    ("Outdoor Remodel - Hardscape", "Paver Patio / Walkway", "Sq Ft", L, 0.25, 6,
     "Excavate, compacted base, sand, standard pavers and polymeric sand."),
    ("Outdoor Remodel - Hardscape", "Segmental Retaining Wall", "Sq Ft", L, 0.40, 14,
     "Block retaining wall per sf of face, gravel base and drainage, under 3' tall."),
    ("Outdoor Remodel - Hardscape", "Grading / Topsoil & Seed", "Sq Ft", L, 0.02, 0.15,
     "Rough grade, topsoil and seed/straw disturbed areas."),
    # Concrete (sub-installed, priced off sub quote per unit)
    ("Concrete", "Concrete Patio / Slab - 4\" Broom Finish", "Sq Ft", S, 9.00, 0,
     "4\" 3500 PSI slab on compacted stone, wire mesh, control joints, broom finish."),
    ("Concrete", "Concrete Driveway - 5\" w/ Rebar", "Sq Ft", S, 10.50, 0,
     "5\" 4000 PSI driveway with rebar/fiber, compacted base, broom finish."),
    ("Concrete", "Concrete Sidewalk - 4' Wide", "Sq Ft", S, 10.00, 0,
     "4\" sidewalk on compacted base, tooled joints, broom finish."),
    ("Concrete", "Stamped / Colored Concrete", "Sq Ft", S, 15.00, 0,
     "4\" slab with integral color, stamp pattern, release and sealer."),
    ("Concrete", "Exposed Aggregate Concrete", "Sq Ft", S, 13.00, 0,
     "4\" exposed aggregate slab, washed finish and sealer."),
    ("Concrete", "Concrete Steps - Per Step (up to 4' wide)", "Each", S, 350, 0,
     "Formed and poured concrete step with rebar, broom finish."),
    ("Concrete", "Concrete Footing (12\"x24\")", "Lin Ft", S, 45, 0,
     "Excavate and pour reinforced concrete footing."),
    ("Concrete", "Concrete Pad - AC / Shed / Generator", "Sq Ft", S, 11.00, 0,
     "Small 4\" pad on compacted stone (small-job premium included)."),
    ("Concrete", "Concrete Demo & Removal", "Sq Ft", S, 4.00, 0,
     "Break out and haul away existing concrete up to 4\" thick."),
    ("Concrete", "Concrete Pump Truck", "Each", S, 650, 0,
     "Line or boom pump when truck access is not available."),
    ("Concrete", "Concrete Small Job Minimum", "Each", S, 1500, 0,
     "Minimum charge for any concrete pour under ~100 sf."),
    ("Concrete", "Concrete Crack Repair", "Lin Ft", L, 0.05, 0.75,
     "Rout, clean and fill cracks with flexible polyurethane sealant."),
    ("Concrete", "Concrete Clean & Seal", "Sq Ft", L, 0.012, 0.15,
     "Pressure wash and apply penetrating or acrylic sealer."),
]

# Fees: (category, name, unit, {seg: (price, cost)}, description); seg None = both segments
FEES = [
    ("Terms & Fees", "Net 30 Payment Terms Adjustment (per $100)", "$100", {"RES": (5, 0), "COM": (5, 0)},
     "Net 30 invoice terms add 5% to the contract. Qty = invoice subtotal / 100 (e.g. $24,000 = qty 240). Due-on-completion pricing does not include this line."),
    ("Terms & Fees", "Net 60 Payment Terms Adjustment (per $100)", "$100", {"COM": (8, 0)},
     "Net 60 invoice terms add 8% to the contract. Qty = invoice subtotal / 100."),
    ("Terms & Fees", "Philadelphia Job Logistics (per day)", "Day", {"COM": (195, 95)},
     "City parking, loading-zone/meter fees, tolls and extended travel for Philadelphia jobs. Qty = site days."),
    ("Terms & Fees", "Commercial Mobilization", "Each", {"COM": None},
     "Mobilize crew, tools and site protection; site orientation and safety setup."),
    ("Terms & Fees", "COI / Additional Insured & Vendor Compliance", "Each", {"COM": (175, 50)},
     "Certificate of insurance with additional insured / waiver of subrogation, vendor portal and W-9 setup."),
    ("Terms & Fees", "After-Hours / Weekend Premium (per labor hr)", "Hour", {"COM": None, "RES": None},
     "Added per labor hour for work outside 7am-5pm Mon-Fri at the client's request."),
]


def round_price(x):
    if x < 20:
        return math.ceil(x * 100 - 1e-6) / 100
    if x < 500:
        return float(math.ceil(x - 1e-6))
    return float(math.ceil(x / 5 - 1e-6) * 5)


def price_item(seg, kind, qty, mat):
    """Return (price, cost, material_cost) for one unit."""
    s = SEGMENTS[seg]
    if kind == L:
        cost = qty * s["labor_cost"] + mat
        return round_price(qty * s["labor_price"] + mat * s["mat_markup"]), cost, mat
    if kind == S:
        cost = qty * (1 + s["sub_pm_pct"])
        return round_price(qty * s["sub_multiplier"]), cost, 0
    if kind == "MAT":
        return s["mat_markup"], 1.00, 1.00
    if kind == "SUB":
        return s["sub_multiplier"], 1 + s["sub_pm_pct"], 0
    if kind == "PASS":  # third-party cost marked up like materials
        return round_price(qty * s["mat_markup"]), qty, 0
    if kind == "PERMIT":
        return 1.00, 1.00, 0
    if kind == "PMHRS":
        return round_price(qty * 85 if seg == "RES" else qty * 110), qty * PM_HOURLY, 0
    raise ValueError(kind)


def build_rows():
    rows = []
    for seg in SEGMENTS:
        s = SEGMENTS[seg]
        for cat, name, unit, kind, qty, mat, desc in ITEMS:
            price, cost, mcost = price_item(seg, kind, qty, mat)
            rows.append(dict(seg=seg, cat=cat, name=name, unit=unit, desc=desc,
                             price=price, cost=round(cost, 2), mat=mcost, kind=kind))
        for cat, name, unit, by_seg, desc in FEES:
            if seg not in by_seg:
                continue
            pc = by_seg[seg]
            if name.startswith("Commercial Mobilization"):
                cost = 3 * s["labor_cost"] + PM_HOURLY + 75
                pc = (round_price(3 * s["labor_price"] + 110 + 150), cost)
            elif name.startswith("After-Hours"):
                pc = (round(s["labor_price"] * 0.5), s["sub_rate"] * 0.5)
            rows.append(dict(seg=seg, cat=cat, name=name, unit=unit, desc=desc,
                             price=float(pc[0]), cost=round(pc[1], 2), mat=0, kind="FEE"))
    return rows


def check(rows):
    problems, flags = [], []
    for r in rows:
        if r["kind"] in ("PERMIT", "FEE") and r["cost"] == 0:
            continue
        gp = (r["price"] - r["cost"]) / r["price"]
        r["gp"] = gp
        if r["kind"] != "PERMIT" and gp < MIN_GP - 1e-9:
            problems.append(f"{r['seg']} {r['name']}: GP {gp:.1%}")
        mp = r["mat"] / r["price"] if r["kind"] == L else 0
        r["mat_pct"] = mp
        if mp > MAX_MATERIAL_PCT:
            flags.append(f"{r['seg']} {r['name']}: material {mp:.0%} of price")
    return problems, flags


# Housecall Pro price book (services) columns. If your exported file's header row
# differs, change only these labels -- the data order stays the same.
HEADER = ["Category", "Name", "Description", "Price", "Cost", "Unit of measure",
          "Taxable", "Online booking enabled"]


def write_csv(rows):
    path = OUT / "foxfern_housecall_pricebook.csv"
    with path.open("w", newline="") as f:
        w = csv.writer(f)
        w.writerow(HEADER)
        for r in rows:
            w.writerow([f"{r['seg']} - {r['cat']}", r["name"], r["desc"],
                        f"{r['price']:.2f}", f"{r['cost']:.2f}", r["unit"],
                        # PA: contractors pay sales tax on materials; real-property
                        # improvements are not taxed to the customer.
                        "FALSE", "FALSE"])
    return path


def write_xlsx(rows):
    wb = Workbook()
    bold = Font(bold=True)
    head_fill = PatternFill("solid", fgColor="2F4F3A")
    head_font = Font(bold=True, color="FFFFFF")
    inp_fill = PatternFill("solid", fgColor="FFF2CC")

    # Inputs
    ws = wb.active
    ws.title = "Inputs"
    ws["A1"] = "Fox & Fern - Pricing Inputs (yellow cells are editable)"
    ws["A1"].font = Font(bold=True, size=14)
    inputs = [
        ("PM weekly pay", PM_WEEKLY_PAY, "PM_Pay"),
        ("PM payroll burden %", PM_BURDEN, "PM_Burden"),
        ("PM hours per week", PM_WEEKLY_HOURS, "PM_Hours"),
        ("Minimum gross profit %", MIN_GP, "Min_GP"),
        ("Max material cost % of price", MAX_MATERIAL_PCT, "Max_Mat"),
        ("Residential sub crew rate / hr", SEGMENTS["RES"]["sub_rate"], "RES_Sub"),
        ("Residential PM hrs per field hr", SEGMENTS["RES"]["pm_ratio"], "RES_PMRatio"),
        ("Residential labor sell rate / hr", SEGMENTS["RES"]["labor_price"], "RES_Rate"),
        ("Residential material markup (x)", SEGMENTS["RES"]["mat_markup"], "RES_Mat"),
        ("Residential sub quote multiplier (x)", SEGMENTS["RES"]["sub_multiplier"], "RES_SubX"),
        ("Residential PM % added to sub quotes", SEGMENTS["RES"]["sub_pm_pct"], "RES_SubPM"),
        ("Commercial sub crew rate / hr", SEGMENTS["COM"]["sub_rate"], "COM_Sub"),
        ("Commercial PM hrs per field hr", SEGMENTS["COM"]["pm_ratio"], "COM_PMRatio"),
        ("Commercial labor sell rate / hr", SEGMENTS["COM"]["labor_price"], "COM_Rate"),
        ("Commercial material markup (x)", SEGMENTS["COM"]["mat_markup"], "COM_Mat"),
        ("Commercial sub quote multiplier (x)", SEGMENTS["COM"]["sub_multiplier"], "COM_SubX"),
        ("Commercial PM % added to sub quotes", SEGMENTS["COM"]["sub_pm_pct"], "COM_SubPM"),
        ("Net 30 terms adjustment %", 0.05, "Net30"),
    ]
    for i, (label, val, nm) in enumerate(inputs, start=3):
        ws.cell(i, 1, label)
        c = ws.cell(i, 2, val)
        c.fill = inp_fill
        if "%" in label:
            c.number_format = "0%"
        elif "(x)" in label or "hrs per" in label or "hours" in label:
            c.number_format = "0.00"
        else:
            c.number_format = "$#,##0.00"
        wb.defined_names[nm] = DefinedName(nm, attr_text=f"Inputs!$B${i}")
    r = len(inputs) + 4
    ws.cell(r, 1, "Calculated").font = bold
    calc = [
        ("PM loaded cost / hr", "=PM_Pay*(1+PM_Burden)/PM_Hours", "PM_Hourly"),
        ("Residential loaded labor cost / hr", "=RES_Sub+PM_Hourly*RES_PMRatio", "RES_Cost"),
        ("Residential labor GP %", "=(RES_Rate-RES_Cost)/RES_Rate", None),
        ("Residential rate needed for min GP", "=RES_Cost/(1-Min_GP)", None),
        ("Commercial loaded labor cost / hr", "=COM_Sub+PM_Hourly*COM_PMRatio", "COM_Cost"),
        ("Commercial labor GP %", "=(COM_Rate-COM_Cost)/COM_Rate", None),
        ("Commercial rate needed for min GP", "=COM_Cost/(1-Min_GP)", None),
    ]
    for j, (label, f, nm) in enumerate(calc, start=r + 1):
        ws.cell(j, 1, label)
        c = ws.cell(j, 2, f)
        c.number_format = "0.0%" if "GP %" in label else "$#,##0.00"
        if nm:
            wb.defined_names[nm] = DefinedName(nm, attr_text=f"Inputs!$B${j}")
    ws.column_dimensions["A"].width = 42
    ws.column_dimensions["B"].width = 14

    # Job estimator
    je = wb.create_sheet("Job Estimator")
    je["A1"] = "Quick Job Estimator - enter labor, materials and subs; get the price"
    je["A1"].font = Font(bold=True, size=14)
    est = [
        ("Segment (RES or COM)", "RES", None),
        ("Total labor DAYS (1 tech x 8 hrs)", 0, None),
        ("Additional labor HOURS", 0, None),
        ("Material cost ($, your cost)", 0, "$#,##0.00"),
        ("Specialty sub quotes ($, their price)", 0, "$#,##0.00"),
        ("Dumpster / other pass-through cost ($)", 0, "$#,##0.00"),
        ("Net 30 terms? (Y/N)", "N", None),
    ]
    for i, (label, val, fmt) in enumerate(est, start=3):
        je.cell(i, 1, label)
        c = je.cell(i, 2, val)
        c.fill = inp_fill
        if fmt:
            c.number_format = fmt
    seg = 'B3="COM"'
    lines = [
        ("Total labor hours", "=B4*8+B5", "0.0", None),
        ("Labor price", f"=B11*IF({seg},COM_Rate,RES_Rate)", "$#,##0", f"=B11*IF({seg},COM_Cost,RES_Cost)"),
        ("Materials price", f"=B6*IF({seg},COM_Mat,RES_Mat)", "$#,##0", "=B6"),
        ("Subcontractor price", f"=B7*IF({seg},COM_SubX,RES_SubX)", "$#,##0",
         f"=B7*(1+IF({seg},COM_SubPM,RES_SubPM))"),
        ("Pass-through price", f"=B8*IF({seg},COM_Mat,RES_Mat)", "$#,##0", "=B8"),
        ("Subtotal", "=SUM(B12:B15)", "$#,##0", "=SUM(C12:C15)"),
        ("Net 30 adjustment", '=IF(B9="Y",B16*Net30,0)', "$#,##0", "=0"),
        ("TOTAL PRICE", "=B16+B17", "$#,##0", "=C16+C17"),
        ("Gross profit $", "=B18-C18", "$#,##0", None),
        ("Gross profit %", "=IF(B18=0,0,B19/B18)", "0.0%", None),
        ("Material cost % of price", "=IF(B18=0,0,B6/B18)", "0.0%", None),
        ("Check", '=IF(B18=0,"",IF(B20<Min_GP,"BELOW MIN GP - raise price",'
                  'IF(B21>Max_Mat,"Material heavy - GP OK, watch it","OK")))', None, None),
    ]
    je.cell(10, 1, "Results").font = bold
    je.cell(10, 2, "Price").font = bold
    je.cell(10, 3, "Cost").font = bold
    for i, (label, f, fmt, cf) in enumerate(lines, start=11):
        je.cell(i, 1, label)
        c = je.cell(i, 2, f)
        if fmt:
            c.number_format = fmt
        if cf:
            cc = je.cell(i, 3, cf)
            cc.number_format = "$#,##0"
    je["A18"].font = bold
    je["B18"].font = bold
    je["A24"] = ("In Housecall Pro: add 'Remodel Labor - Crew Day' (qty = days), 'Remodel Labor - Per Hour' "
                 "(qty = extra hours), 'Materials (per $1 of cost)' (qty = material $), "
                 "'Specialty Subcontractor (per $1)' (qty = sub quote $) and, if Net 30, the Net 30 line "
                 "(qty = subtotal / 100). The totals will match this sheet.")
    je["A24"].alignment = Alignment(wrap_text=True, vertical="top")
    je.merge_cells("A24:C27")
    je.column_dimensions["A"].width = 42
    je.column_dimensions["B"].width = 16
    je.column_dimensions["C"].width = 14

    # Price book
    pb = wb.create_sheet("Price Book")
    cols = ["Segment", "Category", "Name", "Unit", "Price", "Cost", "GP %", "Material % of price", "Description"]
    for j, h in enumerate(cols, start=1):
        c = pb.cell(1, j, h)
        c.font = head_font
        c.fill = head_fill
    for i, r in enumerate(rows, start=2):
        vals = [r["seg"], r["cat"], r["name"], r["unit"], r["price"], r["cost"], None, r.get("mat_pct", 0), r["desc"]]
        for j, v in enumerate(vals, start=1):
            pb.cell(i, j, v)
        pb.cell(i, 5).number_format = "$#,##0.00"
        pb.cell(i, 6).number_format = "$#,##0.00"
        pb.cell(i, 7, f"=IF(E{i}=0,0,(E{i}-F{i})/E{i})").number_format = "0.0%"
        pb.cell(i, 8).number_format = "0%"
    for col, w in zip("ABCDEFGHI", (9, 40, 48, 9, 11, 11, 8, 10, 90)):
        pb.column_dimensions[col].width = w
    pb.freeze_panes = "A2"
    pb.auto_filter.ref = f"A1:I{len(rows) + 1}"

    path = OUT / "foxfern_pricing_calculator.xlsx"
    wb.save(path)
    return path


if __name__ == "__main__":
    rows = build_rows()
    problems, flags = check(rows)
    print(f"PM loaded cost/hr: ${PM_HOURLY:.2f}")
    for k, s in SEGMENTS.items():
        gp = (s["labor_price"] - s["labor_cost"]) / s["labor_price"]
        print(f"{k}: labor cost ${s['labor_cost']:.2f}/hr, sells ${s['labor_price']:.2f}/hr, GP {gp:.1%}")
    print(f"{len(rows)} items -> {write_csv(rows).name}, {write_xlsx(rows).name}")
    if problems:
        raise SystemExit("Below minimum GP:\n  " + "\n  ".join(problems))
    if flags:
        print("Material-heavy items (GP still above minimum):\n  " + "\n  ".join(flags))
