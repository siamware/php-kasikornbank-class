# KasikornBank Class

Unoffical Kasikornbank API Class for PHP.

```php
$kbank = new KasikornBank($username, $password, $cookie_path);

// Check if the session in the cookie is still valid. If not, then login again.
if (!$kbank->CheckSession()) {
	$kbank->Login();
}

// Get Today's Statement.
print_r($kbank->GetTodayStatement("XXX-X-XXXXX-X"));

```

---

## Support Me?

If you like my project, consider donating me. Thank you! <3

[ [Donate with Paypal](https://paypal.me/likecyber) or [Donate with PromptPay](https://promptpay.io/0913147533) ]

Paypal: likecyber.unlimit@gmail.com

PromptPay: 091-314-7533 (Thiranat Mahattanobon)

BTC Address: 3ASp8h7zo1exHJ2ZGNbEdu5kahY5XoNerN

ETH Address: 0x7d0068B8ba02F18e79abdFC5e33B94830A6c93fA

BCH Address: bitcoincash:qpa0dkuq3mwdy2ja0w4ujkjs29jrdy8qlugt5hjvef

## Installation

It is pretty simple to utilize this class, you just need to require it.

```php
require_once("KasikornBank.class.php");
```

## Initialization

Simple initialization with Login Credentials. (Username and Password)

$cookie_path can be leave empty if you want to use temp file.

It is best to specify cookie file path for security reasons.

```php
$kbank = new KasikornBank($username, $password, $cookie_path);
$kbank = new KasikornBank($username, $password); // Use default temp directory for temp file.
$kbank = new KasikornBank($username, $password, "./tmp/"); // Use ./tmp/ as temp directory for temp file.
$kbank = new KasikornBank($username, $password, "./cookie.txt"); // Use ./cookie.txt for cookie file.

```
## Functions

### [function setCredentials ($username, $password)](https://github.com/likecyber/php-kasikornbank-class/blob/master/KasikornBank.class.php#L44-L49)

You can set Login Credentials with this function, this function will NOT destroy current cookie session.

### [function setCookieFile ($cookie_path = null)](https://github.com/likecyber/php-kasikornbank-class/blob/master/KasikornBank.class.php#L51-L63)

You can set Cookie Path with this function, it will use default temp directory to create cookie file by default.

You can change temp directory by set it with directory path, or you can set it with file name to set exact cookie file name.

### [function Login ()](https://github.com/likecyber/php-kasikornbank-class/blob/master/KasikornBank.class.php#L101-L122)

You can login with this function, Login Credentials are required.

### [function Logout ()](https://github.com/likecyber/php-kasikornbank-class/blob/master/KasikornBank.class.php#L124-L135)

You can logout with this function, this function will destroy current cookie session.

### [function CheckSession ()](https://github.com/likecyber/php-kasikornbank-class/blob/master/KasikornBank.class.php#L137-L140)

You can check if the cookie session is still valid with this function.

This function is not guaranteed to be correct due of K-Online mechanism.

### [function GetBalance ($account_number = null, $retry_login = true)](https://github.com/likecyber/php-kasikornbank-class/blob/master/KasikornBank.class.php#L142-L161)

You can get your current account balance with this function.

If $account_number parameter is specified, the result of $account_number will be returned directly.

### [function GetAccountID ($account_number = null, $retry_login = true)](https://github.com/likecyber/php-kasikornbank-class/blob/master/KasikornBank.class.php#L163-L183)

You can get your Account ID with this function.

If $account_number parameter is specified, the result of $account_number will be returned directly.

### [function ParseStatement ($response = null)](https://github.com/likecyber/php-kasikornbank-class/blob/master/KasikornBank.class.php#L185-L203)

You can parse CSV Statement with this function. (There is no needed to use this function unless you know what it does.)

### [function GetStatement ($account_number, $start_date = null, $end_date = null, $retry_login = true, $retry_token = true)](https://github.com/likecyber/php-kasikornbank-class/blob/master/KasikornBank.class.php#L205-L264)

You can get Statement with this function, $start_date and $end_date parameters are needed to be "Y-m-d" format.

### [function GetTodayStatement ($account_number, $retry_login = true, $retry_token = true)](https://github.com/likecyber/php-kasikornbank-class/blob/master/KasikornBank.class.php#L266-L298)

You can get Today's Statement with this function.

---

## Useful Variables
These variables can be used if you need them.

- (string) $this->response : Body Response from Curl execution with UTF-8 encoding.
- (int) $this->http_code : HTTP Code from Curl execution.

- (array) $this->_AccountID : You can take Account ID from this variable.

- (array) $this->curl_options : Allow you to set extra Curl Options. (You can modify this variable.)

---

### Note
- CURLOPT_TIMEOUT can be set with $this->curl_options.
- CURLOPT_SSL_VERIFYPEER can be turn off with $this->curl_options.
- It is best to specify cookie file path for security reasons.
- Make sure to disable access to cookie file from website visitors.
- $this->request()  will automatically convert encoding from Windows-874 to UTF-8.
- "org.apache.struts.taglib.html.TOKEN" field will be automatically filled if available when POST.
- Account Number or Account ID can be used as $account_number in Statement() and GetTodayStatement() functions.
- GetStatement() function can not get today Statement, you need to use GetTodayStatement() function instead.
- GetStatement() function will get Statement in the last 30 days (not included today) by default.
- setCredentials() function will NOT destroy current cookie session when called.
- If you set $retry_login parameter to true, the session will automatically re-login if the session become invalid.
- If you set $retry_token parameter to true, the token will automatically re-obtain if the token is unavailable.

---

## Licenses

KasikornBank Class is 100% free and open-source.

<a rel="license" href="http://creativecommons.org/licenses/by/4.0/"><img alt="Creative Commons License" style="border-width:0" src="https://i.creativecommons.org/l/by/4.0/88x31.png" /></a><br />This work is licensed under a <a rel="license" href="http://creativecommons.org/licenses/by/4.0/">Creative Commons Attribution 4.0 International License</a>.

Copyright 2018-2019 Likecyber

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
