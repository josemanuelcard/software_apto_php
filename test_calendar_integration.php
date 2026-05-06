<?php

require_once 'includes/CalendarIntegrator.php';

$bookingUrl = 'https://ical.booking.com/v1/export?t=b5694e69-7846-40fe-97ca-6fbd57f2dcd5';
$airbnbUrl = 'https://www.airbnb.com.co/calendar/ical/1302398377373776230.ics?s=14b0fbf2ce3f9a577984f237647b8186';

$integrator = new CalendarIntegrator();
$allOccupiedDates = [];

// --- Process Booking.com Calendar ---
echo "Fetching Booking.com calendar...\n";
$bookingIcalContent = $integrator->fetchIcalUrl($bookingUrl);

if ($bookingIcalContent) {
    echo "Parsing Booking.com events...\n";
    $bookingEvents = $integrator->parseIcalData($bookingIcalContent);
    $bookingOccupiedDates = $integrator->getOccupiedDates($bookingEvents);
    $allOccupiedDates = array_merge($allOccupiedDates, $bookingOccupiedDates);
    echo "Found " . count($bookingOccupiedDates) . " occupied dates from Booking.com.\n";
} else {
    echo "Failed to fetch Booking.com calendar.\n";
}

echo "\n";

// --- Process Airbnb Calendar ---
echo "Fetching Airbnb calendar...\n";
$airbnbIcalContent = $integrator->fetchIcalUrl($airbnbUrl);

if ($airbnbIcalContent) {
    echo "Parsing Airbnb events...\n";
    $airbnbEvents = $integrator->parseIcalData($airbnbIcalContent);
    $airbnbOccupiedDates = $integrator->getOccupiedDates($airbnbEvents);
    $allOccupiedDates = array_merge($allOccupiedDates, $airbnbOccupiedDates);
    echo "Found " . count($airbnbOccupiedDates) . " occupied dates from Airbnb.\n";
} else {
    echo "Failed to fetch Airbnb calendar.\n";
}

// --- Combine and Display All Unique Occupied Dates ---
$allOccupiedDates = array_unique($allOccupiedDates);
sort($allOccupiedDates);

echo "\n--- All Unique Occupied Dates ---\n";
if (!empty($allOccupiedDates)) {
    foreach ($allOccupiedDates as $date) {
        echo $date . "\n";
    }
    echo "\nTotal unique occupied dates: " . count($allOccupiedDates) . "\n";
} else {
    echo "No occupied dates found from either calendar.\n";
}

?>