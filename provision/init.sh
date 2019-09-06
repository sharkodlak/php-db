apt-get install -y apt-transport-https lsb-release ca-certificates

apt-get update
apt-get install -y composer curl git nginx php7.3-fpm php-dom php-zip unzip zip

if [ ! -e /usr/local/bin/composer ]; then
	php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
	EXPECTED_SIGNATURE=$(wget -q -O - https://composer.github.io/installer.sig)
	ACTUAL_SIGNATURE=$(php -r "echo hash_file('SHA384', '/tmp/composer-setup.php');")

	if [ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]; then
		>&2 echo 'ERROR: Invalid composer installer signature'
	else
		php /tmp/composer-setup.php -- --install-dir=/usr/local/bin --filename=composer
	fi
fi

cd /vagrant && sudo -u vagrant composer install

. /vagrant/vendor/sharkodlak/development/init.sh
