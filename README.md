# dbc
*store DB change per agile story*

## Overview
The dbc (= DB changes) system is designed to create a change file for the DB per agile story. It is based on the approach that feature branches are merged back to the master branch and need to be applied then on a set of production servers.

## Usage
just call http://myserver/mypath/dbc/ and let the fun begin :)

### Configuration
 1. execute dbc/changes/init_1.sql
 2. copy dbc_example.ini to dbc.ini and update to your needs
 
## Dependencies
The software is self-contained and uses

* [Bootstrap](http://getbootstrap.com/)
* [jQuery](http://jquery.com/)
 
## License
Copyright 2014 georg.roesch@gmail.com

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.