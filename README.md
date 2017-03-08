# Envoy Deployments
Deploy all the things!

A simple zero downtime deployment for basic, composer and Laravel projects.

This is using [Laravel Envoy](https://laravel.com/docs/5.4/envoy)

## Simple Usage
`envoy run deploy --path=/var/www/example.com --repo=git@github.com:TNovalis/example.git`

## What is something:stuff in console?
That is if you want to integrate this in something to automatically deploy projects and give realtime status updates on the stage. If time allows, I may release an app on GitHub to do this.

## Anything else?
- You can run migrations by appending `--migrate`
- Specificy branch with `--branch` (`--branch=v2`) (defaults to master)
- And set environment with `--env` (`--env=local`) (defaults to staging)
