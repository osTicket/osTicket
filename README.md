osTicket
========
osTicket is a widely-used open source support ticket system. It seamlessly
integrates inquiries created via email, phone and web-based forms into a
simple easy-to-use multi-user web interface. Manage, organize and archive
all your support requests and responses in one place while providing your
customers with accountability and responsiveness they deserve.

How osTicket works for you
--------------------------
  1. Users create tickets via your website, email, or phone
  1. Incoming tickets are saved and assigned to agents
  1. Agents help your users resolve their issues
 
osTicket is an attractive alternative to higher-cost and complex customer
support systems; simple, lightweight, reliable, open source, web-based and
easy to setup and use. The best part is, it's completely free.

Installation
------------
osTicket now supports bleeding-edge installations. The easiest way to
install the software and track updates is to clone the public repository.
Create a folder on you web server (using whatever method makes sense for
you) and cd into it. Then clone the repository (the folder must be empty!):

    git clone https://github.com/osTicket/osTicket-1.7 .

osTicket uses the git flow development model, so you’ll need to switch to
the develop branch in order to see the bleeding-edge feature additions.

    git checkout develop 

Follow the usual install instructions (beginning from Manual Installation
above), except, don't delete the setup/ folder. For this reason, such an
installation is not recommended for a public-facing support system.

Help
----
Visit the [wiki](http://osticket.com/wiki/Home) or the
[forum](http://osticket.com/forums/). And if you'd like professional help
managing your osTicket installation, 
[commercial support](http://osticket.com/support/) is available.

Contributing
------------
Create your own fork of the project and use
[git-flow](https://github.com/nvie/gitflow) to create a new feature. Once
the feature is published in your fork, send a pull request to begin the
conversation of integrating your new feature into osTicket.

License
-------
osTicket is released under the GPL2 license. See the included LICENSE.txt
file for the gory details of the General Public License.

osTicket is supported by several magical open source projects including:

  * [HTMLawed](http://www.bioinformatics.org/phplabware/internal_utilities/htmLawed)
  * [PasswordHash](http://www.openwall.com/phpass/)
  * [PEAR](http://pear.php.net/package/PEAR)
  * [PEAR/Auth_SASL](http://pear.php.net/package/Auth_SASL)
  * [PEAR/Mail](http://pear.php.net/package/mail)
  * [PEAR/Net_SMTP](http://pear.php.net/package/Net_SMTP)
  * [PEAR/Net_Socket](http://pear.php.net/package/Net_Socket)
  * [PEAR/Serivces_JSON](http://pear.php.net/package/Services_JSON)
  * [phplint](http://antirez.com/page/phplint.html) 
