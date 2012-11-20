# CodeIgnitor EWS Library
Is a custom built Codeignitor Library to eases the intergration of connecting to your Microsoft Exchange Server via the Exchange Web Services SOAP API, all functions are there to help you get your development complete ASAP. 

# Dependencies
* PHP 5.2+
* cURL with NTLM support (7.23.0+ recommended)
* Exchange 2007 or 2010*
* James Armes PHP-EWS ](https://github.com/jamesiarmes/php-ews)
* CodeIgnitor 2.0+ (http://codeignitor.com)


## Installation
Clone the project from git into the desired location.

```
git clone git@github.com:philipcsaplar/CodeIgnitor_EWS_Library CodeIgnitor_EWS_Library
```

Installation is really simple, i have kept the folder structure of Codeignitor so all you need to do is copy and paste the files into there respective folders.

application/controller/test_ews.php - Test functions to help you get started.

application/libraries/EWS/ - James Armes PHP-EWS

application/libraries/EWS/lock_and_load.php  - Bootstrap file that helps load all the classes from PHP-EWS

application/libraries/Ews.php - Codeignitor Library


## Usage
I have created a test_ews.php file under the application/controller folder , in it i have created some functions that will help you understand how to use the Library.

## Resources
* [CodeIgnitor 2.0+]
(http://codeigniter.com/)
* [PHP Exchange Web Services Wiki](https://github.com/jamesiarmes/php-ews/wiki)
* [Exchange 2007 Web Services Reference](http://msdn.microsoft.com/library/bb204119\(v=EXCHG.80\).aspx)
* [Exchange 2010 Web Services Reference](http://msdn.microsoft.com/library/bb204119\(v=exchg.140\).aspx)

## Support
All questions should use the [issue queue](https://github.com/philipcsaplar/CodeIgnitor_EWS_Library/issues). This allows the community to contribute to and benefit from questions or issues you may have. Any support requests sent to my email address will be directed here.


### Other Contributions
Have you found this library helpful? Why not take a minute to endorse my hard work on [coderwall](http://coderwall.com)! Just click the badge below:

[![endorse](http://api.coderwall.com/philipcsaplar/endorsecount.png)](http://coderwall.com/philipcsaplar)
