Welcome to Secret Santa
=======================

Welcome to the repository for SecretSanta.

###Installation

This project uses Vagrant / Puppet. Install [Vagrant](http://downloads.vagrantup.com/) and [Oracle VirtualBox](https://www.virtualbox.org/wiki/Downloads)
on your machine. First, clone this repository. Then, navigate to the SecretSanta root directory and run the following command:

    $ vagrant up

The first time, this will take some time. It has to download a Debian image, install a full LAMP stack and download Symfony's vendors.
If you are a windows user, make sure to run the following commands before cloning:

    $ git config --global core.autocrlf false
    $ git config --global core.safecrlf true

Add 33.33.33.10 www.secretsanta.dev to your etc/hosts file. You can SSH into the machine with:

    $ vagrant ssh

To stop the machine use:

    $ vagrant halt

This just stops the VM. If you want to remove it use:

    $ vagrant destroy
