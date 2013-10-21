<?php
/***************************************************************************\
*  Based on Eric Vyncke (2008) DKIM-CFG file
*  Configuration file for public and private key
***************************************************************************/
 
// Generate your own key-pair: openssl_pkey_new() generates a new private and public key pair. The public component of the key can be obtained using openssl_pkey_get_public(). 
// copy and paste the content of the public- and private-key files INCLUDING
// the first and last lines (those starting with ----). Below is a working example.


$p_pub_key="-----BEGIN PUBLIC KEY-----
MEwwDQYJKoZIhvcNAQEBBQADOwAwOAIxAMqm8IVtVPOp39MH9cFNikNG+uuRSk0a
2rCGgMmcXNBwy1PX/A9KPOf+z1K4EAbqewIDAQAB
-----END PUBLIC KEY-----
" ;

$p_priv_key="-----BEGIN RSA PRIVATE KEY-----
MIHzAgEAAjEAyqbwhW1U86nf0wf1wU2KQ0b665FKTRrasIaAyZxc0HDLU9f8D0o8
5/7PUrgQBup7AgMBAAECMQCerzYr5L7exihj4RnJMeSQZeZZy704v0sV3rzi9xQk
lx7nEfXfCp+/UEVV7JPqaTECGQDz8GSTIbugN1y3SXVS1pBxjT0cEkn5D+cCGQDU
q/dPMTabDUQyxrZNL5ETe0ulJz79jk0CGQCPIrRxHO8SQMn3hnQASoRxDLYZ3aVo
LmsCGHhcApDdB0xlC6247D9upipiYwNK3MlfGQIYPXh9lLyXXFGKpLr6yUEOUXpP
VQ6ijZYS
-----END RSA PRIVATE KEY-----
";

?>
