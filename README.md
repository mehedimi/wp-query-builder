# WP Query Builder

WP Query Builder is package for developers which can simplify your query writing experience in WordPress.

## Installation
```shell
composer require mehedimi/wp-query-builder
```
Then require the autoload file of composer into your theme or plugin file.

## Basic Usage

### Querying data

Retrieving data from a table
```php
<?php
DB::table('posts')->get();
```
You can select specific columns
```php
<?php
DB::table('posts')->select('post_title')->get();
```
Also, you can apply different types of where clause like bellow
```php
<?php
// This will fetch all posts which has 4 comments
DB::table('posts')->where('comment_count', 4)->get();

// This will fetch all posts which has 100 or grater than 100 comments
DB::table('posts')->where('comment_count', '>=', 100)->get();

// Use whereBetween for where between query
DB::table('posts')->whereBetween('comment_count', [1, 100])->get();
```

### Inserting data
```php
<?php

```