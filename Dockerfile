FROM php:8.2-cli-alpine

RUN apk update &&  \
    apk upgrade && \
    docker-php-ext-install mysqli

COPY ./src /usr/src/app

WORKDIR /usr/src/app

CMD [ "php" , "./spammer.php" ]
