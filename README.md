## Bundle to update Mailchimp Member - interest from backend to mailchimp

Requires https://github.com/1up-lab/contao-mailchimp


ACHTUNG! unterstützt derzeit nur EINE Mailchimp-Liste!!

## Install

Copy to:  
root  
\- src  
\- - hh-com  
\- - - contao-ContaoMailchimpBackendUpdate  

Update your contao installation composer.json
``` code
"repositories": [
    {
        "type": "path",
        "url": "src/hh-com/contao-mailchimp-backendupdate",
        "options": {
                "symlink": true
        }
    }
],
"require": {
    ...
    "hh-com/contao-mailchimp-backendupdate": "@dev",
    ... 
}
```