GimmeTheFile
============

Author: Arno0x0x - https://twitter.com/Arno0x0x


TABLE OF CONTENT
----------------
- Licence
- Genesis of the project
- Server side dependicies
- How to use / How it works
- Notes for apache users
- Notes for nginx users
- TODO
- Credits

Licence
-------
This app is distributed under the terms of the GPLv3 licence available here:
http://www.gnu.org/copyleft/gpl.html


Genesis of the project
----------------------
I started this project as a study on how to bypass some corporate http proxies with antivirus capabilities I've been facing there and there.
I often have to download some binaries that are considered as harmful or unwanted in a corporate environment (fair enough): things like "Cain&Abel", "Mimikatz" etc..., or simply "Eicar" to perform
workstation antivirus tests. As such, these binaries are blocked either by the proxy and/or by the proxy antivirus.

Using some standard web proxy (Glype, labnol python proxy on Google App Engine, and so on) do the trick but only partially, as far as I experienced:
- Some web proxy did not properly handle file downloads
- Even when they did, the corporate http proxy antivirus would still detect the binaries as harmful or unwanted
- Using SSL works but then came in SSL Interception (or SSL inspection) on the corporate proxy
- Even once the corporate proxy was worked around, the local antivirus would shout before I even had the possibility to move it to a VM or elsewhere...

I've been looking around for such a simple app that would not allow "browsing" of a web site with complicated stuff such as performing url rewriting on the fly etc...
but rather a simple application that 1/downloads a file and 2/ makes it stealthy. Didn't find it (maybe I didn't search well enough), and anyway, I wanted to code something, just for the fun and get
my hands on PHP, JQuery and CSS.

I'm not a programmer per say (as you can tell by the code quality) but it pretty much does the job while keeping it simple.


Server side dependencies
------------------------
The app requires PHP 5 or above.
It also relies on libcurl (http://curl.haxx.se/libcurl/php/)

7zip is required only if you plan to choose AES-256 encryption. (http://www.7-zip.org/)


How to use / How it works
-------------------------
Is this section really necessary ? The app is damn simple to use :-)

**How to use:**
- Specify the URL of the file you want to download/transform.
- Choose the transformation type, that is how the file will be transformed once downloaded. It can be either AES-256 encryption or BASE64 encoding (encoding != encryption).
- In case you chose AES-256, you have to specify a password that will be used as an encryption key.
- Additionnal options:
	- The curl request UserAgent used by default is the same as the user's browser. In case it needs to be changed, you can specify an alternative UserAgent
	- The curl request Referer used by default is empty. If required, the Referer can be set to whatever string, OR to FQDN of the host specified in the download URL

The admin page will let you modify some settings and consult the application access log (if they're enabled in the settings).
	
** How it works:**
The app downloads the file using the libcurl library. The libcurl client is configured by default:
	- Follow up to 5 redirects
	- Support http/https and ftp/ftps protocols
	- The maximum file sized allowed is defined in the settings and can be changed through the admin page


Notes for apache users
----------------------
Some files need to be protected from direct http access:
- The settings.ini file
- The log/, tmp/ and result/ folders

The .htaccess provided in the bundle should do the trick though it hasn't been tested.


Notes for nginx users
---------------------
To protect the files from direct http access, add the following section to your nginx.conf file:

```
location /gimmethefile/ {
		location ~ \.(ini|log|zip|b64)$ {
				deny all;    
		}    
}
```
	
TODO
----
There's sure plenty of things that I could see in the roadmap, bearing in mind that I want to keep the application as simple as possible (do one thing, but do it right):
- Fix bugs... (security / features)
- Implement a cleaning mechanism for the "result/" directory (lazy me :-)
- Make more things configurable as a setting in the admin page
- Propose a REST web service to be able to get the same feature in one call from a script


Credits
-------
The GimmeTheFile logo has been designed by "DAVELLAY". Thanks to him !
Feel free to contact me if you like his work and want to get in touch !