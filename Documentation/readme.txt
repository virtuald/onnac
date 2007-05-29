Onnac - Oh No! Not Another CMS
---------------------------------------------------------------------------------

Copyright (c) 2006-2007, Dustin Spicuzza
All rights reserved.
Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

     * Redistributions of source code must retain the above copyright
       notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright
       notice, this list of conditions and the following disclaimer in the
       documentation and/or other materials provided with the distribution.
     * Neither the name of Dustin Spicuzza nor the
       names of any contributors may be used to endorse or promote products
       derived from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY DUSTIN SPICUZZA AND CONTRIBUTORS ``AS IS'' AND ANY
EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL DUSTIN SPICUZZA AND CONTRIBUTORS BE LIABLE FOR ANY
DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
(INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
(INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

Contents
---------------------------------------------------------------------------------

	1. Introduction
	2. Installation
	3. Configuration
	4. Programming API
	5. How to contribute
	6. Contributors
	

1. Introduction
----------------

Onnac (Oh No! Not Another CMS) is a simplistic advanced Content Management 
System (CMS). Onnac is designed generically to help the advanced developer 
implement websites and applications in a (relatively) easy to manage manner.

Of course, at the moment the primary goal of Onnac is to allow me to easily 
manage the websites I administer, so expect development to occur in step with 
the ongoing requirements of active developers. If you have a useful feature 
that you think should be implemented, make a feature request or do it yourself!
If you do it yourself, then you're guaranteed that the feature will get
implemented :). Of course, if you don't like Onnac, then go use another CMS...
theres plenty of them out there. 

As of right now, there is little documentation for this software. That will
eventually change, probably. 

The administrative interface uses the following editor components to edit 
pages:

	FCKEditor: This is a WYSIWYG editor that works very well if you want to 
	insert simple HTML into a page. It will reformat your code and change 
	things it doesn't like, so beware! However, it works very well most of 
	the time. I've found that it will eat some PHP scripts, so avoid using 
	it to edit those! http://www.fckeditor.net
	
	CodePress: Codepress is a syntax highlighter written in Javascript. It
	is extremely lightweight, and works very well in firefox. Does not
	support autoindent or tabbing. http://www.codepress.org
	
	Editarea: Another syntax highlighting editor written in Javascript,
	it supports a number of features that codepress does not, such as
	autoindent, tabbing, fullscreen, and resizable text. 
	http://www.cdolivet.net/editarea/
	

I have used this software in a number of environments, and it works very well 
for me. However, use at your own risk. 

2. Installation
----------------

For installation, see install.txt. 

3. Configuration
-----------------

The file ./include/default.inc.php contains the default settings for many of
the configurable aspects of Onnac. Each item (except where noted) should
be copied into ./include/config.inc.php if you need to change it. 


4. Programming API
-------------------

For API notes, refer to api.txt. At this time, the API is not guaranteed to 
be stable. However, it has been the same for awhile, so it probably
will continue to be unless noted.


5. How to contribute
---------------------

If Onnac doesn't do something that you think it should be able to do, or
if you're just bored and want to write some PHP, then contact me and maybe
I can give you some good ideas. Theres still a lot of ideas I've been
meaning to implement, I've just never gotten around to doing it. See todo.txt

	Dustin Spicuzza (dustin@virtualroadside.com)


6. Contributors
----------------

Thanks to the many :p contributors for helping out with Onnac!

	Jon Anderson (early alpha versions, 0.0.6.x)
