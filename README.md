# Spammer
Yet another test job.

## Test Case
This is a service for sending notifications about expiring subscriptions.

One and three days before the subscription expires, we need to send an email to the user with the text "{username}, your subscription is expiring soon".

## Conditions
1. RDBMS table that contains users data (5,000,000+ rows):

|Field| Description                                                                                    |
|-----|------------------------------------------------------------------------------------------------|
|username| Username                                                                                       |
|email| Email                                                                                          |
|validts| Unix timestamp until which the monthly subscription is valid, or 0 if there is no subscription |
|confirmed|0 or 1 depending on whether the user confirmed his email via the link (after registration, the user receives an email with a unique link to the specified email; if he clicked on the link in the email, this field is set to 1)|
|checked|Whether the email was checked for validation (1) or not (0)|
 |valid|Is the email valid (1) or not (0)|

2. About 80% of users do not have a subscription.
3. Only 15% of users confirm their email (confirmed field).
4. External function check_email( $email )
   Checks the email for validity (the letter will definitely reach a valid email) and returns 0 or 1. The function works from 1 second to 1 minute. Calling a function costs 1 RUB.
5. Function send_email( $from, $to, $text )
   Sends an email. The function operates from 1 second to 10 seconds.

### Restrictions
1. It is necessary to regularly send emails about the expiration of the subscription period to those emails to which the letter will definitely reach.
2. You can use cron.
3. You can create the necessary tables in the DB or change
   existing.
4. For the check_email and send_email functions you need to write “stubs”
5. Don't use OOP
6. Implement queues without using managers
   
## Implementation
Service contains 3 workers:
- *Spammer* - Analyses user's subscriptions and schedules jobs that sends notifications or validates emails
- *Sender* - Sends a notification
- *Checker* - Checks an email

### Requirements
- Docker Desktop >= 4.22.1
- GNU Make >= 3.81

## Building
- Checkout source code
- Create `.env` file based on `.env.dist`
- Build worker's Docker container images: `make build-php`

## Running
- Run DBMS and background workers: `make up`
- Wait for a minute to DB initialization
- Run *Spammer* process: `make spam`

A list of additional Make commands you can get with `make help` command

## Example of execution

```shell
 % make up         
[+] Running 23/23
 ✔ Network spammer_default       Created                                                                                                                                                               0.0s 
 ✔ Container spammer-adminer-1   Started                                                                                                                                                               0.4s 
 ✔ Container spammer-db-1        Started                                                                                                                                                               0.4s 
 ✔ Container spammer-sender-10   Started                                                                                                                                                               0.8s 
 ✔ Container spammer-checker-10  Started                                                                                                                                                               5.4s 
 ✔ Container spammer-checker-5   Started                                                                                                                                                               2.1s 
 ✔ Container spammer-sender-5    Started                                                                                                                                                               8.5s 
 ✔ Container spammer-checker-2   Started                                                                                                                                                               1.0s 
 ✔ Container spammer-sender-6    Started                                                                                                                                                               2.7s 
 ✔ Container spammer-sender-1    Started                                                                                                                                                               7.0s 
 ✔ Container spammer-sender-2    Started                                                                                                                                                               5.3s 
 ✔ Container spammer-checker-1   Started                                                                                                                                                               9.6s 
 ✔ Container spammer-sender-7    Started                                                                                                                                                               1.3s 
 ✔ Container spammer-sender-3    Started                                                                                                                                                               6.6s 
 ✔ Container spammer-sender-8    Started                                                                                                                                                               3.8s 
 ✔ Container spammer-checker-7   Started                                                                                                                                                               6.9s 
 ✔ Container spammer-checker-3   Started                                                                                                                                                               1.6s 
 ✔ Container spammer-sender-9    Started                                                                                                                                                               0.6s 
 ✔ Container spammer-sender-4    Started                                                                                                                                                               1.9s 
 ✔ Container spammer-checker-8   Started                                                                                                                                                               4.0s 
 ✔ Container spammer-checker-6   Started                                                                                                                                                               8.2s 
 ✔ Container spammer-checker-9   Started                                                                                                                                                               0.7s 
 ✔ Container spammer-checker-4   Started     
 
 % make spam
[2023-09-11 18:18:22][DEBUG] Starting to process subscriptions.
[2023-09-11 18:18:22][DEBUG] Getting subscriptions on 2023-09-12 (tomorrow)
[2023-09-11 18:18:29][DEBUG] Processed 10000 items.
[2023-09-11 18:18:36][DEBUG] Processed 20000 items.
[2023-09-11 18:18:44][DEBUG] Processed 30000 items.
[2023-09-11 18:18:51][DEBUG] Processed 40000 items.
[2023-09-11 18:18:59][DEBUG] Processed 50000 items.
[2023-09-11 18:18:59][DEBUG] Processed 50241 items.
[2023-09-11 18:18:59][DEBUG] Total processed items: 50241.
[2023-09-11 18:18:59][DEBUG] To send: 8824, to check: 41417 , invalid email (skipped): 0.
[2023-09-11 18:18:59][DEBUG] Getting subscriptions on 2023-09-14 (+3 days)
[2023-09-11 18:19:06][DEBUG] Processed 10000 items.
[2023-09-11 18:19:12][DEBUG] Processed 20000 items.
[2023-09-11 18:19:19][DEBUG] Processed 30000 items.
[2023-09-11 18:19:26][DEBUG] Processed 40000 items.
[2023-09-11 18:19:33][DEBUG] Processed 50000 items.
[2023-09-11 18:19:39][DEBUG] Processed 60000 items.
[2023-09-11 18:19:47][DEBUG] Processed 70000 items.
[2023-09-11 18:19:55][DEBUG] Processed 80000 items.
[2023-09-11 18:20:03][DEBUG] Processed 90000 items.
[2023-09-11 18:20:10][DEBUG] Processed 100000 items.
[2023-09-11 18:20:10][DEBUG] Processed 100187 items.
[2023-09-11 18:20:10][DEBUG] Total processed items: 100187.
[2023-09-11 18:20:10][DEBUG] To send: 8909, to check: 91278 , invalid email (skipped): 0.
[2023-09-11 18:20:10][DEBUG] Execution time: 108 sec.
[2023-09-11 18:20:10][DEBUG] Memory usage: 2.1 MB
```