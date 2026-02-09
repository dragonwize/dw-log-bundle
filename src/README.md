# DwLog Bundle

## IMPORTANT NOTE 

Logging is a highly context sensitive with many different solutions,
stacks, techs, services, etc. to fit an infinite number of use cases. This logging
implementation is highly opinionated for specific situations. If this approach,
resinates with your situation then copy or fork it. 

This is not and will not be intended as a fully open community and will change 
at will, with no guarantee of breaking contracts.

## Description

A Symfony bundle for logging application events to a database using Monolog and 
Doctrine DBAL, with a web interface for viewing and searching logs.

## Features

- **Database Logging**: Stores logs in a database table using Doctrine DBAL (no ORM)
- **Platform Agnostic**: Uses DBAL Schema representation - works with any database
- **Monolog Integration**: Custom Monolog handler that writes directly to the database
- **Web Interface**: Admin controller with pagination and search functionality
- **Advanced Search**: Filter logs by level, channel, and message content
- **Responsive UI**: Built with Tailwind CSS for a modern look
- **Lightweight**: Uses only DBAL without ORM overhead
- **Easy Setup**: Console command automatically creates the table

## Installation

1. `composer require dragonwize/dw-log-bundle`
2. Optionally create a DBAL connection, and configure in `config/packages/dw_log.yaml`
3. `bin/console dw:log:create-table`
4. Configure monolog to save to the table in `config/packages/monolog.yaml`

Example monolog config:
```yaml
monolog:
    handlers:
        dbal:
            type: service
            id: Dragonwize\DwLogBundle\Monolog\DbalHandler
            level: debug
            channels: ["!event", "!doctrine"]
```

## Ideal Setup

Yes, storing in a SQL database is generally not performant for logs at scale.
But if scale is not your concern and you want something free, easy, and with 
an opinionated DX then this works fine for those purposes. This code attempts to
be performant only so far as it does not inflict on any other concerns.

### Use a separate DBAL connection

This allows you to:
- Store logs in a separate database
- Use different database engines (prod in PostgreSQL, local in Sqlite, etc.)
- Implement different backup/retention strategies per database
- Isolate log performance from application database

### Changing Log Retention

@todo

### Security

This is entirely up to you, if you are not comfortable securing the data stores
and routes, then this may not be for you.
