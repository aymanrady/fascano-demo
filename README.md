# Fascano Demo

## Setup
- Setup `sail`
- `vendor/bin/sail up -d`
- `vendor/bin/sail composer install`
- `vendor/bin/sail npm install`
- migrate & seed
    ```shell
    vendor/bin/sail artisan migrate:fresh --seed
    ```
- `vendor/bin/sail npm run dev`
- navigate to `localhost/partners`
- login using `admin@example.com` & `password`
- navigate to first parther > first resturant > click qr icon to view qr code
- or navigate directly to `localhost/app/menu/1`, open again in an incognito window to join the same order

## Overview

- The demo consists of Partners (users with role "partner") having multiple Restaurants that can be managed.
- A Restaurant contains multiple Tables available for customers
- A Table can be either "Open", "Occupied", or "Waiting"
    - "Open" when it has no pending or processing orders
    - "Occupied" when it has a pending order
    - "Waiting" when it has a processing order - waiting for kitchen to complete.
- An Order starts out in a Pending state, with no items
- Customers can add/remove items from a Pending order as long as no payments have been made
- Customers can place the order and proceed to payment where they can either choose to pay in full or split payments
- All payments made against an order are recorded and used to determine paid/remainder amounts
- Multiple customers can make payments or a single customer can use multiple cards to complete the payment
- Tips are split proportionally between customers

Some assumptions and shortcuts where used, in the form of json columns for order items and order totals.
also some values are stored despite being computable from other pieces of data - order totals.
In a full project implementation cart operations would be stored as domain events and projected to various models for reporting
and display, but as this is a demo, no such considerations were taken.

Furthermore, reconcelliations and refunds are not handled or taken in consideration.
