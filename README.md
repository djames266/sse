sse
===

<b>Simple Secure Email (SSE)</b>

An authentication system for ecommerce sites and customers communicating via email. This is based on S/MIME and DKIM ideas.

<b>Requirements</b>

Currently this package requires PHP 5.1.2 or greater which provides the hash() function.

openssl is also required with your PHP installation to compute and verify RSA signatures.

<b>Quick Script Description<b>
define_database.txt: defines the table and column details used by SSE
key-cfg.php: key-pair configuration file used by SSE (p_mailF.php and p_verifyF.php)
p_init.php: initialises global variables used by SSE
p_mailF.php: SSE's digital signing script
sse_verifier.html: email verifier web page where in users paste in the signed message body.
p_verify.php: stores user input from verifier page into an array. (medium between see_verifier.html and p_verifyF.php)
p_verifyF.php: SSE's signed email verification script using p_verify.php input array
psendverifyCF.php: functions common to both p_mailF.php and p_verifyF.php


<b>Changelog</b>
