#!/usr/bin/env python3
"""Copy host domain files into path packages and rewrite namespaces."""

from __future__ import annotations

import re
import shutil
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1]

REPLACEMENTS: list[tuple[str, str]] = [
    (r"\bApp\\Http\\Resources\\LocationBikeResource\b", "Embiken\\BikeFleet\\Http\\Resources\\LocationBikeResource"),
    (r"\bApp\\Http\\Resources\\LocationReservationResource\b", "Embiken\\BikeRentals\\Http\\Resources\\LocationReservationResource"),
    (r"\bApp\\Http\\Resources\\LocationLineResource\b", "Embiken\\BikeRentals\\Http\\Resources\\LocationLineResource"),
    (r"\bApp\\Http\\Resources\\DayPatchResource\b", "Embiken\\BikeRentals\\Http\\Resources\\DayPatchResource"),
    (r"\bApp\\Services\\Availability\b", "Embiken\\BikeRentals\\Services\\Availability"),
    (r"\bApp\\Exceptions\\OccupancyUnavailable\b", "Embiken\\BikeRentals\\Exceptions\\OccupancyUnavailable"),
    (r"\bApp\\Exceptions\\OutBikesNeedConfirmation\b", "Embiken\\BikeRentals\\Exceptions\\OutBikesNeedConfirmation"),
    (r"\bApp\\Support\\DayStoreSnapshot\b", "App\\Support\\DayStore"),
    (r"\bApp\\Models\\BikeCategory\b", "Embiken\\BikeFleet\\Models\\BikeCategory"),
    (r"\bApp\\Models\\BikeModelVariant\b", "Embiken\\BikeFleet\\Models\\BikeModelVariant"),
    (r"\bApp\\Models\\BikeModel\b", "Embiken\\BikeFleet\\Models\\BikeModel"),
    (r"\bApp\\Models\\BikeReservation\b", "Embiken\\BikeRentals\\Models\\BikeReservation"),
    (r"\bApp\\Models\\Bike\b", "Embiken\\BikeFleet\\Models\\Bike"),
    (r"\bApp\\Models\\ServiceRequest\b", "Embiken\\BikeFleet\\Models\\ServiceRequest"),
    (r"\bApp\\Models\\ServiceEntry\b", "Embiken\\BikeFleet\\Models\\ServiceEntry"),
    (r"\bApp\\Models\\RentalPackageProduct\b", "Embiken\\BikeRentals\\Models\\RentalPackageProduct"),
    (r"\bApp\\Models\\RentalPackage\b", "Embiken\\BikeRentals\\Models\\RentalPackage"),
    (r"\bApp\\Models\\Reservation\b", "Embiken\\BikeRentals\\Models\\Reservation"),
    (r"\bApp\\Models\\Transaction\b", "Embiken\\BikeRentals\\Models\\Transaction"),
    (r"\bApp\\Models\\Customer\b", "Embiken\\BikeRentals\\Models\\Customer"),
    (r"\bApp\\Enums\\BikeSituation\b", "Embiken\\BikeFleet\\Enums\\BikeSituation"),
    (r"\bApp\\Enums\\ServiceStage\b", "Embiken\\BikeFleet\\Enums\\ServiceStage"),
    (r"\bApp\\Enums\\ReservationChannel\b", "Embiken\\BikeRentals\\Enums\\ReservationChannel"),
    (r"\bApp\\Enums\\ReservationStage\b", "Embiken\\BikeRentals\\Enums\\ReservationStage"),
    (r"\bApp\\Enums\\BikeReservationStatus\b", "Embiken\\BikeRentals\\Enums\\BikeReservationStatus"),
    (r"\bApp\\Enums\\ConfirmThreshold\b", "Embiken\\BikeRentals\\Enums\\ConfirmThreshold"),
    (r"\bApp\\Enums\\PackageMeter\b", "Embiken\\BikeRentals\\Enums\\PackageMeter"),
    (r"\bApp\\Enums\\TransactionKind\b", "Embiken\\BikeRentals\\Enums\\TransactionKind"),
    (r"\bApp\\Enums\\TransactionStatus\b", "Embiken\\BikeRentals\\Enums\\TransactionStatus"),
    (r"\bApp\\Observers\\BikeReservationObserver\b", "Embiken\\BikeRentals\\Observers\\BikeReservationObserver"),
    (r"\bApp\\Observers\\ReservationObserver\b", "Embiken\\BikeRentals\\Observers\\ReservationObserver"),
    (r"\bApp\\Observers\\BikeObserver\b", "Embiken\\BikeFleet\\Observers\\BikeObserver"),
    (r"\bApp\\Filament\\Shop\\Resources\\BikeCategories\b", "Embiken\\BikeFleet\\Filament\\Resources\\BikeCategories"),
    (r"\bApp\\Filament\\Shop\\Resources\\BikeModels\b", "Embiken\\BikeFleet\\Filament\\Resources\\BikeModels"),
    (r"\bApp\\Filament\\Shop\\Resources\\BikeModelVariants\b", "Embiken\\BikeFleet\\Filament\\Resources\\BikeModelVariants"),
    (r"\bApp\\Filament\\Shop\\Resources\\Bikes\b", "Embiken\\BikeFleet\\Filament\\Resources\\Bikes"),
    (r"\bApp\\Filament\\Shop\\Resources\\ServiceRequests\b", "Embiken\\BikeFleet\\Filament\\Resources\\ServiceRequests"),
    (r"\bApp\\Filament\\Shop\\Resources\\Customers\b", "Embiken\\BikeRentals\\Filament\\Resources\\Customers"),
    (r"\bApp\\Filament\\Shop\\Resources\\Reservations\b", "Embiken\\BikeRentals\\Filament\\Resources\\Reservations"),
    (r"\bApp\\Filament\\Shop\\Resources\\RentalPackages\b", "Embiken\\BikeRentals\\Filament\\Resources\\RentalPackages"),
    (r"\bApp\\Actions\\Reservations\b", "Embiken\\BikeRentals\\Actions"),
    (r"\bApp\\Actions\\Terminal\\ShowTerminalPrototypeAction\b", "Embiken\\BikeRentals\\Actions\\ShowTerminalPrototypeAction"),
    (r"\bDatabase\\Factories\\BikeCategoryFactory\b", "Embiken\\BikeFleet\\Database\\Factories\\BikeCategoryFactory"),
    (r"\bDatabase\\Factories\\BikeModelVariantFactory\b", "Embiken\\BikeFleet\\Database\\Factories\\BikeModelVariantFactory"),
    (r"\bDatabase\\Factories\\BikeModelFactory\b", "Embiken\\BikeFleet\\Database\\Factories\\BikeModelFactory"),
    (r"\bDatabase\\Factories\\BikeFactory\b", "Embiken\\BikeFleet\\Database\\Factories\\BikeFactory"),
    (r"\bDatabase\\Factories\\ServiceRequestFactory\b", "Embiken\\BikeFleet\\Database\\Factories\\ServiceRequestFactory"),
    (r"\bDatabase\\Factories\\ServiceEntryFactory\b", "Embiken\\BikeFleet\\Database\\Factories\\ServiceEntryFactory"),
    (r"\bDatabase\\Factories\\CustomerFactory\b", "Embiken\\BikeRentals\\Database\\Factories\\CustomerFactory"),
    (r"\bDatabase\\Factories\\ReservationFactory\b", "Embiken\\BikeRentals\\Database\\Factories\\ReservationFactory"),
    (r"\bDatabase\\Factories\\BikeReservationFactory\b", "Embiken\\BikeRentals\\Database\\Factories\\BikeReservationFactory"),
    (r"\bDatabase\\Factories\\RentalPackageProductFactory\b", "Embiken\\BikeRentals\\Database\\Factories\\RentalPackageProductFactory"),
    (r"\bDatabase\\Factories\\RentalPackageFactory\b", "Embiken\\BikeRentals\\Database\\Factories\\RentalPackageFactory"),
    (r"\bDatabase\\Factories\\TransactionFactory\b", "Embiken\\BikeRentals\\Database\\Factories\\TransactionFactory"),
]


def rewrite_text(text: str, extra: list[tuple[str, str]] | None = None) -> str:
    for pattern, repl in REPLACEMENTS + (extra or []):
        text = re.sub(pattern, repl.replace("\\", r"\\"), text, flags=re.M)
    return text


def copy_file(src: Path, dest: Path, extra: list[tuple[str, str]] | None = None) -> None:
    dest.parent.mkdir(parents=True, exist_ok=True)
    dest.write_text(rewrite_text(src.read_text(), extra))


def copy_tree(src: Path, dest: Path, extra: list[tuple[str, str]] | None = None) -> None:
    if not src.exists():
        return
    for path in src.rglob("*"):
        if path.is_file():
            copy_file(path, dest / path.relative_to(src), extra)


FLEET_NS = [
    (r"^namespace App\\Models;", "namespace Embiken\\BikeFleet\\Models;"),
    (r"^namespace App\\Enums;", "namespace Embiken\\BikeFleet\\Enums;"),
    (r"^namespace App\\Observers;", "namespace Embiken\\BikeFleet\\Observers;"),
    (r"^namespace App\\Http\\Resources;", "namespace Embiken\\BikeFleet\\Http\\Resources;"),
    (r"^namespace App\\Filament\\Shop\\Resources", "namespace Embiken\\BikeFleet\\Filament\\Resources"),
    (r"^namespace Database\\Factories;", "namespace Embiken\\BikeFleet\\Database\\Factories;"),
]

RENTALS_NS = [
    (r"^namespace App\\Models;", "namespace Embiken\\BikeRentals\\Models;"),
    (r"^namespace App\\Enums;", "namespace Embiken\\BikeRentals\\Enums;"),
    (r"^namespace App\\Observers;", "namespace Embiken\\BikeRentals\\Observers;"),
    (r"^namespace App\\Http\\Resources;", "namespace Embiken\\BikeRentals\\Http\\Resources;"),
    (r"^namespace App\\Services;", "namespace Embiken\\BikeRentals\\Services;"),
    (r"^namespace App\\Exceptions;", "namespace Embiken\\BikeRentals\\Exceptions;"),
    (r"^namespace App\\Filament\\Shop\\Resources", "namespace Embiken\\BikeRentals\\Filament\\Resources"),
    (r"^namespace App\\Actions\\Reservations;", "namespace Embiken\\BikeRentals\\Actions;"),
    (r"^namespace App\\Actions\\Terminal;", "namespace Embiken\\BikeRentals\\Actions;"),
    (r"^namespace Database\\Factories;", "namespace Embiken\\BikeRentals\\Database\\Factories;"),
]


def main() -> None:
    fleet = ROOT / "packages/bike-fleet"
    rentals = ROOT / "packages/bike-rentals"

    fleet_files = {
        "app/Models/Bike.php": "src/Models/Bike.php",
        "app/Models/BikeCategory.php": "src/Models/BikeCategory.php",
        "app/Models/BikeModel.php": "src/Models/BikeModel.php",
        "app/Models/BikeModelVariant.php": "src/Models/BikeModelVariant.php",
        "app/Models/ServiceRequest.php": "src/Models/ServiceRequest.php",
        "app/Models/ServiceEntry.php": "src/Models/ServiceEntry.php",
        "app/Enums/BikeSituation.php": "src/Enums/BikeSituation.php",
        "app/Enums/ServiceStage.php": "src/Enums/ServiceStage.php",
        "app/Observers/BikeObserver.php": "src/Observers/BikeObserver.php",
        "app/Http/Resources/LocationBikeResource.php": "src/Http/Resources/LocationBikeResource.php",
        "database/factories/BikeFactory.php": "database/factories/BikeFactory.php",
        "database/factories/BikeCategoryFactory.php": "database/factories/BikeCategoryFactory.php",
        "database/factories/BikeModelFactory.php": "database/factories/BikeModelFactory.php",
        "database/factories/BikeModelVariantFactory.php": "database/factories/BikeModelVariantFactory.php",
        "database/factories/ServiceRequestFactory.php": "database/factories/ServiceRequestFactory.php",
        "database/factories/ServiceEntryFactory.php": "database/factories/ServiceEntryFactory.php",
        "database/migrations/tenant/2026_09_02_135240_create_bike_categories_table.php": "database/tenant/migrations/2026_09_02_135240_create_bike_categories_table.php",
        "database/migrations/tenant/2026_09_02_135241_create_bike_models_table.php": "database/tenant/migrations/2026_09_02_135241_create_bike_models_table.php",
        "database/migrations/tenant/2026_09_02_135242_create_bike_model_variants_table.php": "database/tenant/migrations/2026_09_02_135242_create_bike_model_variants_table.php",
        "database/migrations/tenant/2026_09_02_135243_create_bikes_table.php": "database/tenant/migrations/2026_09_02_135243_create_bikes_table.php",
        "database/migrations/tenant/2026_09_02_135246_create_service_requests_table.php": "database/tenant/migrations/2026_09_02_135246_create_service_requests_table.php",
        "database/migrations/tenant/2026_09_02_135247_create_service_entries_table.php": "database/tenant/migrations/2026_09_02_135247_create_service_entries_table.php",
    }

    rentals_files = {
        "app/Models/Customer.php": "src/Models/Customer.php",
        "app/Models/Reservation.php": "src/Models/Reservation.php",
        "app/Models/BikeReservation.php": "src/Models/BikeReservation.php",
        "app/Models/RentalPackage.php": "src/Models/RentalPackage.php",
        "app/Models/RentalPackageProduct.php": "src/Models/RentalPackageProduct.php",
        "app/Models/Transaction.php": "src/Models/Transaction.php",
        "app/Enums/ReservationChannel.php": "src/Enums/ReservationChannel.php",
        "app/Enums/ReservationStage.php": "src/Enums/ReservationStage.php",
        "app/Enums/BikeReservationStatus.php": "src/Enums/BikeReservationStatus.php",
        "app/Enums/ConfirmThreshold.php": "src/Enums/ConfirmThreshold.php",
        "app/Enums/PackageMeter.php": "src/Enums/PackageMeter.php",
        "app/Enums/TransactionKind.php": "src/Enums/TransactionKind.php",
        "app/Enums/TransactionStatus.php": "src/Enums/TransactionStatus.php",
        "app/Observers/ReservationObserver.php": "src/Observers/ReservationObserver.php",
        "app/Observers/BikeReservationObserver.php": "src/Observers/BikeReservationObserver.php",
        "app/Http/Resources/LocationReservationResource.php": "src/Http/Resources/LocationReservationResource.php",
        "app/Http/Resources/LocationLineResource.php": "src/Http/Resources/LocationLineResource.php",
        "app/Http/Resources/DayPatchResource.php": "src/Http/Resources/DayPatchResource.php",
        "app/Services/Availability.php": "src/Services/Availability.php",
        "app/Exceptions/OccupancyUnavailable.php": "src/Exceptions/OccupancyUnavailable.php",
        "app/Exceptions/OutBikesNeedConfirmation.php": "src/Exceptions/OutBikesNeedConfirmation.php",
        "app/Actions/Terminal/ShowTerminalPrototypeAction.php": "src/Actions/ShowTerminalPrototypeAction.php",
        "database/factories/CustomerFactory.php": "database/factories/CustomerFactory.php",
        "database/factories/ReservationFactory.php": "database/factories/ReservationFactory.php",
        "database/factories/BikeReservationFactory.php": "database/factories/BikeReservationFactory.php",
        "database/factories/RentalPackageFactory.php": "database/factories/RentalPackageFactory.php",
        "database/factories/RentalPackageProductFactory.php": "database/factories/RentalPackageProductFactory.php",
        "database/factories/TransactionFactory.php": "database/factories/TransactionFactory.php",
        "database/migrations/tenant/2026_09_02_135239_create_customers_table.php": "database/tenant/migrations/2026_09_02_135239_create_customers_table.php",
        "database/migrations/tenant/2026_09_02_135244_create_rental_packages_table.php": "database/tenant/migrations/2026_09_02_135244_create_rental_packages_table.php",
        "database/migrations/tenant/2026_09_02_135245_create_reservations_table.php": "database/tenant/migrations/2026_09_02_135245_create_reservations_table.php",
        "database/migrations/tenant/2026_09_02_135359_create_rental_package_product_table.php": "database/tenant/migrations/2026_09_02_135359_create_rental_package_product_table.php",
        "database/migrations/tenant/2026_09_02_135400_create_bike_reservation_table.php": "database/tenant/migrations/2026_09_02_135400_create_bike_reservation_table.php",
        "database/migrations/tenant/2026_09_02_135248_create_transactions_table.php": "database/tenant/migrations/2026_09_02_135248_create_transactions_table.php",
        "database/migrations/tenant/2026_09_02_135401_add_bike_situation_reservation_foreign_to_bikes_table.php": "database/tenant/migrations/2026_09_02_135401_add_bike_situation_reservation_foreign_to_bikes_table.php",
    }

    for src, dest in fleet_files.items():
        copy_file(ROOT / src, fleet / dest, FLEET_NS)

    copy_tree(ROOT / "app/Filament/Shop/Resources/BikeCategories", fleet / "src/Filament/Resources/BikeCategories", FLEET_NS)
    copy_tree(ROOT / "app/Filament/Shop/Resources/BikeModels", fleet / "src/Filament/Resources/BikeModels", FLEET_NS)
    copy_tree(ROOT / "app/Filament/Shop/Resources/BikeModelVariants", fleet / "src/Filament/Resources/BikeModelVariants", FLEET_NS)
    copy_tree(ROOT / "app/Filament/Shop/Resources/Bikes", fleet / "src/Filament/Resources/Bikes", FLEET_NS)
    copy_tree(ROOT / "app/Filament/Shop/Resources/ServiceRequests", fleet / "src/Filament/Resources/ServiceRequests", FLEET_NS)

    for src, dest in rentals_files.items():
        copy_file(ROOT / src, rentals / dest, RENTALS_NS)

    copy_tree(ROOT / "app/Actions/Reservations", rentals / "src/Actions", RENTALS_NS)
    copy_tree(ROOT / "app/Filament/Shop/Resources/Customers", rentals / "src/Filament/Resources/Customers", RENTALS_NS)
    copy_tree(ROOT / "app/Filament/Shop/Resources/Reservations", rentals / "src/Filament/Resources/Reservations", RENTALS_NS)
    copy_tree(ROOT / "app/Filament/Shop/Resources/RentalPackages", rentals / "src/Filament/Resources/RentalPackages", RENTALS_NS)

    proto_src = ROOT / "resources/js/pages/Terminal/prototype"
    proto_dest = rentals / "resources/js/Terminal/prototype"
    if proto_src.exists():
        shutil.copytree(proto_src, proto_dest, dirs_exist_ok=True)

    # Rewrite remaining host + tests (not packages we just wrote — already rewritten)
    skip_dirs = {"vendor", "node_modules", "storage", "bootstrap/cache", "packages"}
    for path in ROOT.rglob("*.php"):
        if any(part in skip_dirs for part in path.parts):
            continue
        if path.name == "extract-packages.py":
            continue
        text = path.read_text()
        new = rewrite_text(text)
        if new != text:
            path.write_text(new)

    delete = list(fleet_files) + list(rentals_files)
    delete += [
        "app/Actions/Reservations",
        "app/Actions/Terminal/ShowTerminalPrototypeAction.php",
        "app/Filament/Shop/Resources/BikeCategories",
        "app/Filament/Shop/Resources/BikeModels",
        "app/Filament/Shop/Resources/BikeModelVariants",
        "app/Filament/Shop/Resources/Bikes",
        "app/Filament/Shop/Resources/ServiceRequests",
        "app/Filament/Shop/Resources/Customers",
        "app/Filament/Shop/Resources/Reservations",
        "app/Filament/Shop/Resources/RentalPackages",
        "app/Support/DayStoreSnapshot.php",
        "resources/js/pages/Terminal/prototype",
    ]
    for rel in delete:
        target = ROOT / rel
        if target.is_dir():
            shutil.rmtree(target)
        elif target.exists():
            target.unlink()

    print("extract complete")


if __name__ == "__main__":
    main()
