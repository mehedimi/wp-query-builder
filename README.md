![WP Query Builder](https://banners.beyondco.de/WP%20Query%20Builder.png?theme=light&packageManager=composer+require&packageName=mehedimi%2Fwp-query-builder&pattern=architect&style=style_1&description=An+elegant+query+builder+for+WordPress&md=1&showWatermark=0&fontSize=125px&images=document-download)

## WP Query Builder

WP Query Builder is package for developers, which can simplify query writing experience in WordPress.

- [Installation](#installation)
- [Running SQL Queries](#running-queries)
- [Running Database Queries](#running-database-queries)
    - [Retrieving All Rows From A Table](#retrieving-all-rows-from-a-table)
    - [Retrieving A Single Row](#retrieving-a-single-row)
    - [Aggregates](#aggregates)
- [Joins](#joins)
- [Basic Where Clauses](#basic-where-clauses)
    - [Where Clauses](#where-clauses)
    - [Additional Where Clauses](#additional-where-clauses)
- [Ordering, Grouping, Limit & Offset](#ordering-grouping-limit-and-offset)
    - [Ordering](#ordering)
    - [Grouping](#grouping)
    - [Limit & Offset](#limit-and-offset)
- [Database Transactions](#database-transactions)
- [Defining Relationships (On Demand)](#defining-relationships)
    - [One To One](#one-to-one)
    - [One To Many](#one-to-many)

<a name="introduction"></a>

## Installation

```shell
composer require mehedimi/wp-query-builder
```

If you need extra feature of `WP Query Builder` then you may install `WP Query Builder Extension` Package

```shell
composer require mehedimi/wp-query-builder-ext
```

Then require the autoload file of composer into your theme or plugin file.

<a name="running-queries"></a>

## Running SQL Queries

The `DB` class provides methods for each type of query: `select`, `insert`, `update`, `delete`, `statement` and `affectingStatement`.
<a name="running-a-select-query"></a>

#### Running A Select Query

To run a basic SELECT query, you may use the `select` method on the `DB` class:

```php
$users = DB::select('select * from users where active = ?', [1]);
```

<a name="running-an-insert-statement"></a>

#### Running An Insert Statement

To execute an `insert` statement, you may use the `insert` method on the `DB` class. Like `select`, this method accepts
the SQL query as its first argument and bindings as its second argument:

```php
DB::insert('insert into users (id, name) values (?, ?)', [1, 'Username']);
```

<a name="running-an-update-statement"></a>

#### Running An Update Statement

```php
$affected = DB::update(
        'update users set votes = 100 where name = ?',
        ['Omar']
    );
```

<a name="running-a-delete-statement"></a>

#### Running A Delete Statement

```php
 $deleted = DB::delete('delete from users');
```

<a name="running-database-queries"></a>

## Running Database Queries

<a name="retrieving-all-rows-from-a-table"></a>

#### Retrieving All Rows From A Table

You may use the `table` method provided by the `DB` class to begin a query. The `table` method returns a fluent query
builder instance for the given table, allowing you to chain more constraints onto the query and then finally retrieve
the results of the query using the `get` method:

```php
DB::table('posts')->get();
```

<a name="retrieving-a-single-row"></a>

#### Retrieving A Single Row

If you just need to retrieve a single row from a database table, you may use the `first` method of `DB` class. This
method will return a single `stdClass` object:

```php
$user = DB::table('users')->where('name', 'John')->first();

return $user->email;
```

<a name="aggregates"></a>

### Aggregates

The query builder also provides a variety of methods for retrieving aggregate values like `count`, `max`, `min`, `avg`,
and `sum`. You may call any of these methods after constructing your query:

```php
$users = DB::table('users')->count();

$price = DB::table('orders')->max('price');
```

Of course, you may combine these methods with other clauses to fine-tune how your aggregate value is calculated:

```php
$price = DB::table('orders')
                ->where('finalized', 1)
                ->avg('price');
```

<a name="specifying-a-select-clause"></a>

#### Specifying A Select Clause

You may not always want to select all columns from a database table. Using the `select` method, you can specify a
custom "select" clause for the query:

```php
$users = DB::table('users')
            ->select('name', 'email')
            ->get();
```

The `distinct` method allows you to force the query to return distinct results:

```php
$users = DB::table('users')->distinct()->get();
```

<a name="joins"></a>

## Joins

<a name="inner-join-clause"></a>

#### Inner Join Clause

The query builder may also be used to add join clauses to your queries. To perform a basic "inner join", you may use
the `join` method on a query builder instance. The first argument passed to the `join` method is the name of the table
you need to join to, while the remaining arguments specify the column constraints for the join. You may even join
multiple tables in a single query:

```php
<?php
$users = DB::table('users')
            ->join('contacts', 'users.id', '=', 'contacts.user_id')
            ->join('orders', 'users.id', '=', 'orders.user_id')
            ->select('users.*', 'contacts.phone', 'orders.price')
            ->get();
```

<a name="left-join-right-join-clause"></a>

#### Left Join / Right Join Clause

If you would like to perform a "left join" or "right join" instead of an "inner join", use the `leftJoin` or `rightJoin`
methods. These methods have the same signature as the `join` method:

```php
<?php
$users = DB::table('users')
            ->leftJoin('posts', 'users.id', '=', 'posts.user_id')
            ->get();

$users = DB::table('users')
            ->rightJoin('posts', 'users.id', '=', 'posts.user_id')
            ->get();
```

<a name="advanced-join-clauses"></a>

#### Advanced Join Clauses

You may also specify more advanced join clauses. To get started, pass a closure as the second argument to the `join`
method. The closure will receive a `Mehedi\WPQueryBuilder\Query\Join` instance which allows you to specify constraints
on the "join" clause:

```php
<?php
DB::table('users')
        ->join('contacts', function ($join) {
            $join->on('users.id', '=', 'contacts.user_id')->orOn(/* ... */);
        })
        ->get();
```

If you would like to use a "where" clause on your joins, you may use the `where` and `orWhere` methods provided by
the `Join` instance. Instead of comparing two columns, these methods will compare the column against a value:

```php
DB::table('users')
        ->join('contacts', function ($join) {
            $join->on('users.id', '=', 'contacts.user_id')
                 ->where('contacts.user_id', '>', 5);
        })
        ->get();
```

<a name="basic-where-clauses"></a>

## Basic Where Clauses

<a name="where-clauses"></a>

### Where Clauses

You may use the query builder's `where` method to add "where" clauses to the query. The most basic call to the `where`
method requires three arguments. The first argument is the name of the column. The second argument is an operator, which
can be any of the database's supported operators. The third argument is the value to compare against the column's value.

For example, the following query retrieves users where the value of the `votes` column is equal to `100` and the value
of the `age` column is greater than `35`:

```php
$users = DB::table('users')
                ->where('votes', '=', 100)
                ->where('age', '>', 35)
                ->get();
```

For convenience, if you want to verify that a column is `=` to a given value, you may pass the value as the second
argument to the `where` method. Laravel will assume you would like to use the `=` operator:

```php
    $users = DB::table('users')->where('votes', 100)->get();
```

As previously mentioned, you may use any operator that is supported by your database system:

```php
$users = DB::table('users')
                ->where('votes', '>=', 100)
                ->get();

$users = DB::table('users')
                ->where('votes', '<>', 100)
                ->get();

$users = DB::table('users')
                ->where('name', 'like', 'T%')
                ->get();
```

<a name="or-where-clauses"></a>

### Or Where Clauses

When chaining together calls to the query builder's `where` method, the "where" clauses will be joined together using
the `and` operator. However, you may use the `orWhere` method to join a clause to the query using the `or` operator.
The `orWhere` method accepts the same arguments as the `where` method:

```php
$users = DB::table('users')
                    ->where('votes', '>', 100)
                    ->orWhere('name', 'John')
                    ->get();
```

<a name="where-nested"></a>

### Where Nested

If you need to group where condition within parentheses, you may use the `whereNested` method

```php
$users = DB::table('users')
            ->where('votes', '>', 100)
            ->whereNested(function($query) {
                $query->where('name', 'Some Name')
                      ->where('votes', '>', 50);
            }, 'or')
            ->get();

```

The example above will produce the following SQL:

```sql
select * from users where votes > 100 or (name = 'Some Name' and votes > 50)
```

<a name="additional-where-clauses"></a>

### Additional Where Clauses

**whereBetween**

The `whereBetween` method verifies that a column's value is between two values:

```php
$users = DB::table('users')
           ->whereBetween('votes', [1, 100])
           ->get();
// For or query
$users = DB::table('users')
           ->whereBetween('votes', [1, 100], 'or')
           ->get();
```

**whereNotBetween**

The `whereNotBetween` method verifies that a column's value lies outside of two values:

```php
$users = DB::table('users')
                    ->whereNotBetween('votes', [1, 100])
                    ->get();
// For or where
$users = DB::table('users')
                    ->whereNotBetween('votes', [1, 100], 'or')
                    ->get();

```

**whereIn / whereNotIn**

The `whereIn` method verifies that a given column's value is contained within the given array:

```php
$users = DB::table('users')
                    ->whereIn('id', [1, 2, 3])
                    ->get();
// For or query
$users = DB::table('users')
                    ->whereIn('id', [1, 2, 3], 'or')
                    ->get();
```

The `whereNotIn` method verifies that the given column's value is not contained in the given array:

```php
$users = DB::table('users')
                    ->whereNotIn('id', [1, 2, 3])
                    ->get();
// For or query                    
$users = DB::table('users')
                    ->whereNotIn('id', [1, 2, 3], 'or')
                    ->get();
```

**whereNull / whereNotNull**

The `whereNull` method verifies that the value of the given column is `NULL`:

```php
$users = DB::table('users')
                ->whereNull('updated_at')
                ->get();
```

The `whereNotNull` method verifies that the column's value is not `NULL`:

```php
$users = DB::table('users')
                ->whereNotNull('updated_at')
                ->get();
```

**whereColumn / orWhereColumn**

The `whereColumn` method may be used to verify that two columns are equal:

```php
$users = DB::table('users')
                ->whereColumn('first_name', 'last_name')
                ->get();
// For or query
$users = DB::table('users')
                ->whereColumn('first_name', 'last_name', 'or')
                ->get();
```

You may also pass a comparison operator to the `whereColumn` method:

```php
$users = DB::table('users')
                ->whereColumn('updated_at', '>', 'created_at')
                ->get();
```

<a name="ordering-grouping-limit-and-offset"></a>

## Ordering, Grouping, Limit & Offset

<a name="ordering"></a>

### Ordering

<a name="orderby"></a>

#### The `orderBy` Method

The `orderBy` method allows you to sort the results of the query by a given column. The first argument accepted by
the `orderBy` method should be the column you wish to sort by, while the second argument determines the direction of the
sort and may be either `asc` or `desc`:

```php
$users = DB::table('users')
                ->orderBy('name', 'desc')
                ->get();
```

To sort by multiple columns, you may simply invoke `orderBy` as many times as necessary:

```php
$users = DB::table('users')
                ->orderBy('name', 'desc')
                ->orderBy('email', 'asc')
                ->get();
```

<a name="grouping"></a>

### Grouping

<a name="groupby"></a>

#### The `groupBy` Method

As you might expect, the `groupBy` method may be used to group the query results:

```php
$users = DB::table('users')
                ->groupBy('account_id')
                ->get();
```

<a name="limit-and-offset"></a>

### Limit & Offset

You may use the `offset` and `limit` methods to limit the number of results returned from the query or to skip a given
number of results in the query:

```php
$users = DB::table('users')
                ->offset(10)
                ->limit(5)
                ->get();
```
<a name="database-transactions"></a>
## Database Transactions

You may use the `transaction` method provided by the `DB` class to run a set of operations within a database transaction. If an exception is thrown within the transaction closure, the transaction will automatically be rolled back. If the closure executes successfully, the transaction will automatically be committed. You don't need to worry about manually rolling back or committing while using the `transaction` method:
```php
DB::transaction(function () {
    DB::table('posts')->where('ID', 2)->update(['title' => 'new title']);

    DB::table('posts')->where('ID', 4)->delete();
});
```
<a name="manually-using-transactions"></a>
#### Manually Using Transactions

If you would like to begin a transaction manually and have complete control over rollbacks and commits, you may use the `beginTransaction` method provided by the `DB` facade:
```php
DB::beginTransaction();
```

You can rollback the transaction via the `rollback` method:
```php
DB::rollback();
```
Lastly, you can commit a transaction via the `commit` method:
```php
DB::commit();
```
> **Note**  
> All the 4 methods support `$flags` and `$name` parameter supported by `mysqli`, like bellow:
```php
DB::beginTransaction(MYSQLI_TRANS_START_READ_WRITE, 'some-random-name');
DB::commit(MYSQLI_TRANS_START_READ_WRITE, 'some-random-name');
DB::rollback(MYSQLI_TRANS_START_READ_WRITE, 'some-random-name');
DB::transaction(function () {
    // Do something
}, MYSQLI_TRANS_START_READ_WRITE, 'some-random-name');
```
 
<a name="defining-relationships"></a>

## Defining Relationships

You could define your relationship on query time.

<a name="one-to-one"></a>

### One To One

A one-to-one relationship is a very basic type of database relationship. For example, a `post` might be associated with
one `postmeta` called `color`. To define this relationship, we need to just call `withOne` method on query builder

```php
DB::table('posts')->withOne('color', function(WithOne $relation){
    $relation->from('postmeta');
}, 'post_id', 'ID')->get();
```

This will return all posts with color. Let's say you have two posts and each post have one postmeta row. So output of
this query will be like bellow

```php
[
  [
     'ID' => 1,
     'post_title' => 'Post 1'
     'color' => [
          'post_id' => 1,
          'name' => 'color',
          'value' => 'red'
     ] 
  ],
  [
     'ID' => 2,
     'post_title' => 'Post Two'
     'color' => [
          'post_id' => 2,
          'name' => 'color',
          'value' => 'green'
     ] 
  ]
]
```

And executes following those queries.

```sql
 select * from posts
 select * from postmeta where post_id in (1, 2)
```

<a name="one-to-many"></a>

### One To Many

A one-to-many relationship is used to define relationships where a single item has one or more child
item. For example, a post may have an infinite number of comments. To use One-To-Many relation in Query Builder just
use `withMany` method.

```php
DB::table('posts')->withMany('comments', function(WithMany $relation){
    $relation->from('comments');
}, 'comment_post_ID', 'ID')->get();
```

Sample output will be like bellow

```php
[
  [
     'ID' => 1,
     'post_title' => 'Post 1'
     'comments' => [
       [
          'comment_post_ID' => 1,
          'comment_content' => 'Something',
          ...
       ]
     ] 
  ],
  [
     'ID' => 2,
     'post_title' => 'Post Two'
     'comments' => [
       [
          'comment_post_ID' => 2,
          'comment_content' => 'Something',
          ...
       ]
     ] 
  ]
]
```

And execute following two queries

```sql
select * from posts
select * from comments where comment_post_ID in (1, 2)
```
