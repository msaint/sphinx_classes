These classes help facilitate interaction with the Sphinx open source search server through it's SphinxQL protocol.

SphinxClient.class.php is a generic php class that helps construct a SphinxQL query

SymfonySphinxPager.class.php is specific to the symfony framerwork as it extends the sfPager class. It interacts with the SphinxClient class to fetch collections of query results by page