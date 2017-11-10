# Drupal back-end slides

> Back end Drupal to create your own presentation slides

> This project doesn't not work alone. It will only provide JSON response to be read by a front-end app, such as VueJS front-end slides

## Usage

First you need to install [composer](https://getcomposer.org/download) on a Apache Serveur with preferably PHP 7.0 or up.

Then create a new database on MySQL or MariaDB (eg. slides)

``` bash
$ git clone https://github.com/lahode/back-drupal-slides slides
$ cd slides
$ composer install
```

Go to the URL related to your project (eg. http://myslides.com) and start the basic installation of Drupal.

Once it's done, you can start creating your new slideshow: http://myslides.com/node/add/book.

Then, create each slides, http://myslides.com/node/add/standard_slide, your need to attach on your initial slideshow by selecting "Book outline" on your right before saving.

You can order your slide as you wish on the following URL : http://myslides.com/admin/structure/book

To retrieve the URL, go to http://myslides.com/slides/{my_slideshow_ID} (my_slideshow_ID = your slideshow Node ID)
