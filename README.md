## Setup Test Environment

Install [VirtualBox](https://www.virtualbox.org/), [Vagrant](https://www.vagrantup.com/) and [Ansible](http://docs.ansible.com/ansible/latest/installation_guide/intro_installation.html).
Checkout the repository and start the virtual machine:
```
git clone --recursive git@github.com:atript/lh-wp-plugin.git
cd lh-wp-plugin/
vagrant up
```
Vagrant will add a static IP address to the VM.
To access the Wordpress site, add the following line to your hosts file:
```
192.168.1.10    local.loghero.io
```
Now you can access the Wordpress site: http://local.loghero.io

## Testing

To run the plugin tests, execute:
```
vagrant ssh -c 'cd /var/www/html/wp-content/plugins/loghero && phpunit'
```

To run the SDK tests, install phpunit with composer:
```
vagrant ssh -c 'cd /var/www/html/wp-content/plugins/loghero/sdk && composer install'
```
Then execute phpunit:
```
vagrant ssh -c 'cd /var/www/html/wp-content/plugins/loghero/sdk && php vendor/phpunit/phpunit/phpunit test'
```
