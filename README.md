# Composer Package for running your Application on AWS Lambda
Let's hit those 5 W's

## Who
- Paul Hulett <paulhulett@gmail.com>
- https://github.com/PaulJulio
- https://linkedin.com/in/paulhulett
- https://twitter.com/paulhulett
- https://facebook.com/paulhulett

## Where
https://github.com/PaulJulio/php-composer-on-aws-lambda

## When
In early 2016 I became interested in using Amazon's Lambda service for internet-of-things API applications, mainly because of the difficulty
of implementing my Amazon Echo app outside of Amazon's stack (as of this writing, Amazon still does not support the SNI standard for HTTPS).

## What
The goal of this project is to enable the use of a standard LAMP application constructed around the Composer Package dependency system
within the AWS Lambda runtime environment. It is being set up in such a way that it is easily updated should any of the Amazon Labmda
architecture get updated, particularly the virtual machines that serve as the host machine.

I am specifically not embedding any compiled library in this repo because

- If you can't spin up an AWS virtual machine using the provided scripts, you are not in the target audience
- Chances are, any advanced project will need other compiled binaries, so the initial steps will be required anyway
- Chances are, any advanced project will need to change the php binary that is installed to include more/less functionality
- It doesn't sound like a good idea to me
- I don't want to be responsible for your binary file

## How
As of this writing, I have the following sketch of a process:

- Add this package to your project via composer
- Use the provided utilities to spin up an AWS VM that mirrors what you will run on in Lambda DONE
- From that machine, install this project via git DONE
- On the AWS machine, use the provided utilities to compile PHP
- Download the binary to your project
- Use the provided utilities to package up your project for use on Lambda
- Submit your project to Lambda

Nice to have:

- Do all that remote stuff via locally executed utilities. (In progress, using a package to send commands to the remote machine)
