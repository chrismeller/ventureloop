Description
===========
VentureLoop (ventureloop.com) is a job board specializing in job postings for venture-backed companies. Unfortunately they only provide email job alerts - no RSS feeds. I wanted to fix that and add them to my existing job-search workflow. That's where this class comes in...

Don't Be Evil
-------------
If your goal is to create an RSS feed of recent jobs that match your search you should only need to get a single page of results each time. Don't be a jerk and insist on hammering the VentureLoop server to get every single job that matches... it's just not necessary, and it's likely to ruin things for all of us if they get tired of it.

License
-------

	Copyright 2012 Chris Meller
	
	Licensed under the Apache License, Version 2.0 (the "License");
	you may not use this file except in compliance with the License.
	You may obtain a copy of the License at
	
	    http://www.apache.org/licenses/LICENSE-2.0
	
	Unless required by applicable law or agreed to in writing, software
	distributed under the License is distributed on an "AS IS" BASIS,
	WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
	See the License for the specific language governing permissions and
	limitations under the License.

Usage
=====

Include the class in your code, make sure a default timezone is set, and find your next exciting job:

````$jobs = VentureLoop::factory()->search( 'php', 'Austin, TX' )->jobs();````

You can check out the ``example.php`` file to see a full example that outputs a table of jobs.