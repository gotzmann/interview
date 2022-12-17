# Simple Checkout App

This is a small PHP implementation of pet checkout system. 

Some highlights:

- Implemented with OOP and TDD principles in mind
- Uses [Comet](https://github.com/gotzmann/comet) as backend for REST API endpoints
- Stores data into local SQLite database  
- Dockerfile to automate CI/CD within cloud environments
- Unit tests coverage for main parts of application 

Please see Task part below to dig into the full problem description.

## FAQ

**How to start it?**

- You need machine with PHP7.4 / PHP8 or Docker installed
- Type "make install" within project folder to create empty "checkout.db" file to store data
- Type "php app.php start" to start local app or "make up" to start it as Docker container

**How to try API?**

- POST http://localhost/api/v1/basket/add with JSON body like { "sku": "A" } to add item to basker
- GET http://localhost/api/v1/checkout/total to get total sum of money of basket contents

**How to test the code?**

- Just type "make test" and wait for a second 
- OR type any legal PHPunit command like "vendor/bin/phpunit tests"

## Task

Let’s implement the code for a supermarket checkout that calculates the total price of a
number of items.

An item has the following attributes:

- SKU
- Unit Price

Our goods are priced individually. Some items are multi priced: buy n of them, and they’ll cost
you less than buying them individually. For example, item ‘A’ might cost $50 individually, but
this week we have a special offer: buy three ‘A’s and they’ll cost you $130.

**Here is an example of prices:**

 SKU | Unit | Price Special Price
-----|------|---------------------
 A   | $50  | 3 for $130
 B   | $30  | 2 for $45
 C   | $20  | 
 C   | $15  |

Our checkout accepts items in any order, so that if we scan a B, an A, and another B, we’ll
recognize the two B’s and price them at 45 (for a total price so far of 95). Because the pricing
changes frequently, we need to be able to pass in a set of pricing rules each time we start
handling a checkout transaction.

**The interface to the checkout should look like:**

```
co = new CheckOut(pricing_rules);
co.scan(item); 
co.scan(item);
price = co.total();
```

**Here are some examples of cases:**

 Items      | Total 
------------|------
 A, B       | $80  
 A, A       | $100  
 A, A, A    | $130  
 C, D, B, A | $115 

**As a bonus:**

1. How would you implement rules like “10% off the total if you spend over $200”

2. How would you handle multiple same SKU rules?
