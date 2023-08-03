# Legacy Pages
The legacy service is what runs on PHP 7.3 and is used for old pages (pre-React.js days). All the pages in this service does not require authentication service or any other service. 

## Notes

I made this code open source for education/reference purpose only. Please make note of the following points before using the code.

* There might be some security holes
* None of our production code rely on any of legacy code
* Many pages/services found in this repo (like Audio etc) are no longer part of the site
* Feel free to copy code or idea from this repo
* Be careful when copying and run a second set of eyes before using the code
* This is definitely not be the prettiest code base but its well structured although the current codebase structure is completely different as it is microservice architecture based instead of a monolith architecture like this legacy code
* The codebase uses raw PHP instead of a framework like Yii or Laravel that would be a more practical approach if you are starting from ground up.
* If you see references to a code in any file (e.g, .htaccess file) and you can't find the file, thats because the legacy code has been retired for quite a while and either the service is no longer needed or it has already been ported to the new architecture/codebase and removed from the legacy code (i.e, this repo).
