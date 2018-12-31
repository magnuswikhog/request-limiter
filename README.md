# request-limiter
A simple class for limiting the request interval and number of requests per day either by IP or session (cookie).

## Installation
Simply copy the file `class-request-limiter.php` to wherever you want it and include it in your PHP script. 

Note that the class is named `RequestLimiter`, so if you're using autoloading you'll need to rename the file to `RequestLimiter.php`.


## Usage

You can limit visitors either by their IP number or by sessions/cookies. IP-limiting will work even if the user has disabled cookies, but has the disadvantage of possibly limiting "innocent" users who happen to be on the same IP-number as someone else who has exceeded the request limits.

### Limiting by IP-number

	  require_once( './class-request-limiter.php' );

    // Limit requests to min 10 seconds interval, max 50 requests per IP per day
    $requestLimiter = new RequestLimiter('ip', 'my-script', 10, 50, "./ip-request-limiter/my-script.json");
    $requestLimiter->performRequest();

    // ... do all the things that your script does ...
        
In the example above, a visitor must wait 10 seconds between requests, and they cannot make more than 50 requests per 24 hours. Request limiting is based on the users IP-number and information about all requests to this script is stored in `"./ip-request-limiter/my-script.json"`.

### Limiting by session (requires cookies)

	  require_once( './class-request-limiter.php' );

    // Limit requests to min 10 seconds interval, max 50 requests per session per day
    $requestLimiter = new RequestLimiter('session', 'my-script', 10, 50);
    $requestLimiter->performRequest();

    // ... do all the things that your script does ...
    
This example is the same as the previous one, but limiting is based on sessions, which requires that the user has cookies enabled. Information about the users requests is stored in the session data, not in a file as with IP-limiting.


	
