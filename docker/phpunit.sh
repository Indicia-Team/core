#!/bin/bash

# Set the port on which the warehouse will be accessible
# to the host.
export PORT=8010

# The phpunit container is built to replicate the CI environment
# allowing us to run tests locally before pushing commits.
# The container is built with webserver and database together.
docker-compose -f docker-compose-phpunit.yml build \
  --build-arg UID=$(id -u) \
  --build-arg GID=$(id -g) \
  --build-arg USER=$(id -un) \
  --build-arg GROUP=$(id -gn) \
  --build-arg PHP_VERSION=7.3 \
  --build-arg PG_VERSION=13 \
  --build-arg PORT=$PORT
# When the container is brought up, the database will start 
# followed by Apache which will respond to http requests.
# This is performed in the background.
docker-compose -f docker-compose-phpunit.yml up -d

# Wait for warehouse to be up
echo "Waiting for warehouse..."
until curl --silent --output outputfile http://localhost:${PORT}; do
  sleep 1
done
echo "Warehouse is up."

# Backup any existing config files on the host
BACKUP=( \
  ../application/config/config.php \
  ../application/config/indicia.php \
  ../application/config/database.php \
  ../application/config/email.php \
  ../modules/rest_api/config/rest.php \
  ../modules/spatial_index_builder/config/spatial_index_builder.php \
  ../modules/request_logging/config/request_logging.php
)
for FILE in ${BACKUP[@]}; do
  if [ -f $FILE ]; then
    mv -f $FILE ${FILE}.phpunit-backup
  fi
done;

# Enable the phpunit module in config.php (meaning initialise() is not tested)
DIR=../application/config
cp ${DIR}/config.php.travis ${DIR}/config.php
# Alter site domain as apache is on a different port compared to Travis
sed -i "s/127.0.0.1/127.0.0.1:${PORT}/" ${DIR}/config.php
# Provide a config file for the rest_api, spatial_index_builder and request_logging modules
DIR=../modules/rest_api/config
cp ${DIR}/rest.php.travis  ${DIR}/rest.php
DIR=../modules/spatial_index_builder/config
cp ${DIR}/spatial_index_builder.php.travis  ${DIR}/spatial_index_builder.php
DIR=../modules/request_logging/config
cp ${DIR}/request_logging.example.php ${DIR}/request_logging.php

# Run the tests in the container as the host user.
# (This ensures that e.g. log files created by phpunit are equally
# accessible to the Apache process and the host user too.)
# The XDEBUG_CONFIG is to allow breakpoints to be triggered as tests run.
# 172.17.0.1 is the IP address of the Docker host seen from a container.
# The idekey is for a suitably configured Visual Studio Code debugging client.
docker exec -t -e XDEBUG_CONFIG="idekey=VSCODE client_host=172.17.0.1" docker_phpunit_1 sh -c '
  runuser -u $USER -- pwd && \
    vendor/bin/phpunit --stderr --configuration phpunit-config-test.xml && \
    vendor/bin/phpunit --stderr --configuration phpunit-setup-check-test.xml && \
    vendor/bin/phpunit --stderr --configuration phpunit-home-test.xml && \
    vendor/bin/phpunit --stderr --configuration phpunit-home-test.xml && \
    vendor/bin/phpunit --stderr --configuration phpunit-tests.xml
'


# Restore backed-up files.
for FILE in ${BACKUP[@]}; do
  if [ -f ${FILE}.phpunit-backup ]; then
    mv -f ${FILE}.phpunit-backup $FILE
  fi
done;

# Clean up.
rm -f cookiefile
rm -f outputfile

docker-compose -f docker-compose-phpunit.yml down
