# TFT

TFT (Tester for Triplestore) is a script PHP to pass tests through a sparql endpoint.

# install JMeter for protocol tests
```
wget http://mirrors.standaloneinstaller.com/apache//jmeter/binaries/apache-jmeter-5.4.1.tgz
tar xvzf apache-jmeter-5.4.1.tgz
mv  apache-jmeter-5.4.1 jmeter
rm apache-jmeter-5.4.1.tgz
```

## How to use it ?

You can read the doc here: https://bordercloud.github.io/tft-reports/

## Usage with Travis Ci

Example of project with Travis Ci and TFT :

* [OpenLink Virtuoso version community 7/stable](https://github.com/BorderCloud/tft-virtuoso7-stable)
* [Blazegraph 2.1.5](https://github.com/BorderCloud/tft-blazegraph)
* [Jena-Fuseki 4.0.0](https://github.com/BorderCloud/tft-jena-fuseki)

## Usage with Jenkins

Jenkins will be read the reports Junit/XML with this line :

```
TFT/junit/*junit.xml
```

## Usage sparql-auth of Virtuoso
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

## Read the last score with SPARQL

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

## License

TFT (c)2021 by Karima Rafes, BORDERCLOUD

TFT is licensed under a Creative Commons Attribution-ShareAlike 4.0 International License.

You should have received a copy of the license along with this work. If not, see http://creativecommons.org/licenses/by-sa/4.0/.
