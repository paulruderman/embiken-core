#!/usr/bin/env bash

php artisan tenants:deprovision --all --force
php artisan migrate:fresh --seed --force
php artisan platform:ensure-users
php artisan db:seed-fake-tenant --all --confirm-demo
