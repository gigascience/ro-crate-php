<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use ROCrate\ROCrate;
use ROCrate\Publication;

/**
 * Summary of PublicationTest
 */
class PublicationTest extends TestCase {
    /**
     * Tests the constructor
     * @return void
     */
    public function testPublication(): void {
        $publication = new Publication("https://doi.org/10.1109/TCYB.2014.2386282", "CreativeWork");

        $this->assertEquals("https://doi.org/10.1109/TCYB.2014.2386282", $publication->getId());
        $this->assertEquals(true, in_array("CreativeWork", $publication->getTypes()));
    }

    /**
     * Tests the toArray method
     * @return void
     */
    public function testToArray(): void {
        $publication = new Publication("https://doi.org/10.1109/TCYB.2014.2386282");
        $publication->addPropertyPair("author", "https://orcid.org/0000-0002-8367-6908", true)
            ->addPropertyPair("author", "https://orcid.org/0000-0003-0690-4732")
            ->addPropertyPair("author", "https://orcid.org/0000-0003-3960-0583")
            ->addPropertyPair("author", "https://orcid.org/0000-0002-6953-3986")
            ->addProperty("identifier", "https://doi.org/10.1109/TCYB.2014.2386282")
            ->addProperty("issn", "2168-2267")
            ->addProperty("name", "Topic Model for Graph Mining")
            ->addProperty("journal", "IEEE Transactions on Cybernetics")
            ->addProperty("datePublished", "2015")
            ->addProperty("creditText", "J. Xuan, J. Lu, G. Zhang and X. Luo, \"Topic Model for Graph Mining,\" in IEEE Transactions on Cybernetics, vol. 45, no. 12, pp. 2792-2803, Dec. 2015, doi: 10.1109/TCYB.2014.2386282. keywords: {Data mining;Chemicals;Hidden Markov models;Inference algorithms;Data models;Vectors;Chemical elements;Graph mining;latent Dirichlet allocation (LDA);topic model;Graph mining;latent Dirichlet allocation (LDA);topic model}");
        $array = $publication->toArray();

        $this->assertEquals("https://doi.org/10.1109/TCYB.2014.2386282", $array["@id"]);
        $this->assertEquals(true, in_array("ScholarlyArticle", $array["@type"]));
        $this->assertEquals("https://orcid.org/0000-0002-6953-3986", $array["author"][3]["@id"]);
        $this->assertEquals("2168-2267", $array["issn"]);
        $this->assertEquals("2015", $array["datePublished"]);
        $this->assertEquals("J. Xuan, J. Lu, G. Zhang and X. Luo, \"Topic Model for Graph Mining,\" in IEEE Transactions on Cybernetics, vol. 45, no. 12, pp. 2792-2803, Dec. 2015, doi: 10.1109/TCYB.2014.2386282. keywords: {Data mining;Chemicals;Hidden Markov models;Inference algorithms;Data models;Vectors;Chemical elements;Graph mining;latent Dirichlet allocation (LDA);topic model;Graph mining;latent Dirichlet allocation (LDA);topic model}", $array["creditText"]);
    }
}