# TFT
===

TFT (Tester for Triplestore) is a script PHP to pass tests through a sparql endpoint.


## How to use it ?

You can read the doc here: https://bordercloud.github.io/tft-reports/

## Usage with Travis Ci

Example of project with Travis Ci and TFT :

* [Blazegraph 2.2.0](https://github.com/BorderCloud/tft-blazegraph)
* [Jena-Fuseki 3.8.0](https://github.com/BorderCloud/tft-jena-fuseki)
* [Stardog community 5.3.3](https://github.com/BorderCloud/tft-stardog)
* [OpenLink Virtuoso version community 7/stable](https://github.com/BorderCloud/tft-virtuoso7-stable)


## Old docs

Installation of CURL
============
If, you have errors about CURL, probably you need to install the lib php-curl.

Example with ubuntu & fedora :
```
# apt-get install php5-curl
apt-get install php70w-common
or
# yum install php5-curl
yum / dnf install php70w-common
```

Usage with Jenkins & Jena-Fuseki
==================

```
rm -rf TFT
git clone  --recursive https://github.com/BorderCloud/TFT.git
cd TFT

./tft-testsuite -a -t fuseki -q http://example.com:3030/tests/query -u http://example.com:3030/tests/update

./tft \
-t fuseki \
-q http://example.com:3030/tests/query \
-u http://example.com:3030/tests/update \
-tt fuseki -tq http://127.0.0.1/ds/query -tu http://127.0.0.1/ds/update \
-o ./junit \
-r ${BUILD_URL} \
--softwareName=Fuseki --softwareDescribeTag=v${VERSIONFUSEKI}  --softwareDescribe="${BUILD_TAG}#${FILEFUSEKI}"

./tft-score \
-t fuseki \
-q http://example.com:3030/tests/query \
-u http://example.com:3030/tests/update \
-r ${BUILD_URL}
```

Jenkins will be read the reports Junit/XML with this line :

```
TFT/junit/*junit.xml
```


Usage with Virtuoso
==================
```
git clone --recursive https://github.com/BorderCloud/TFT.git
cd TFT

#copie tests in a RDF database
./tft-testsuite -a \
                -t virtuoso \
                -q 'http://database/sparql-auth/' \
                -u 'http://database/sparql-auth/' \
                -l LOGIN -p 'PASS'

#tests Virtuoso
./tft  \
      -t virtuoso \
      -q 'http://database/sparql-auth/' \
      -u 'http://database/sparql-auth/' \
      -tt virtuoso \
      -tq http://databasetotest/sparql/ \
      -tu http://databasetotest/sparql/ \
      -o ./junit \
      -r https://marketplace.stratuslab.eu/marketplace/metadata/MvJPyzt00KDfRS-vM5gUEfhlr-R \
      --softwareName="Virtuoso Open-Source Edition"  --softwareDescribeTag=v7.1.1  --softwareDescribe=7.1.1-dev.3211-pthreads \
      -l LOGIN -p 'PASSWORD'

#Calculate the score
./tft-score \
      -t virtuoso \
      -q 'http://database/sparql-auth/' \
      -u 'http://database/sparql-auth/' \
      -r https://marketplace.stratuslab.eu/marketplace/metadata/MvJPyzt00KDfRS-vM5gUEfhlr-R \
      -l LOGIN -p 'PASSWORD'
```

Read the last score with SPARQL
===============================

Example :
```
SELECT *
WHERE
{
	GRAPH ?graph {
       ?service a sd:Service ;
               sd:server ?server ;
               sd:testedBy ?tester ;
               sd:testedDate ?LastDate.
       ?server git:name ?serverName ;
               git:describeTag ?serverVersion ;
               git:describe ?serverVersionBuild .
       ?tester  git:name ?testerName ;
               git:describeTag ?testerVersion  .
			   ?service sq:scoreTest ?score .
			   ?service sq:totalTest ?total .
       }
FILTER(STR(xsd:date(?LastDate)) = STR(xsd:date(NOW())))
}
```

License
=======

TFT (c)2018 by Karima Rafes - INRIA (in 2014), BORDERCLOUD

TFT is licensed under a Creative Commons Attribution-ShareAlike 4.0 International License.

You should have received a copy of the license along with this work. If not, see http://creativecommons.org/licenses/by-sa/4.0/.
