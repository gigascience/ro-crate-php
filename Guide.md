
# Usage Guide for ro-crate-php
Below are some notes to pay attention to when developers are using the tool to manipulate RO-Crate Metadata file. The note is GigaDB-oriented. This note is written to help ease the use of the tool to create or manipulate the RO-Crate Metadata file concerning GigaDB datasets by removing unnecessary and only emphasizing relevant technical details about the specific standard of RO-Crate 1.2.

---

## Overview
This is a PHP tool to create and manipulate Research Object Crate. Please refer to the repository's *[README.md](https://github.com/gigascience/ro-crate-php/tree/main)* for more details. Below are the high-level steps instructing the creation of the metadata file for a GigaDB dataset from scratch. The created file may not be perfect but ought to be able to provide sufficient description of the dataset. An example created following the flow is in the assets directory of the repository above.

**Version**: [1.0]  
**Last Updated**: [2025-08-18]

---

## Note
The general rule is that we use the @id construct (<b>true</b> flag if using the add/removePropertyPair methods) when referring to another entity, we otherwise use a plain literal (<b>false</b> flag if using the add/removePropertyPair methods). There are exceptions for specific constructs not following the rules.

Another reminder is to add the entity to the crate before or after the creation of the entity.

Also, only one entity with the same ID has to be created.

In addition, name of an entity should be human-readable if it exists.

The metadata file always has ro-crate-metadata.json as the @id. The preview file has ro-crate-preview.html as the @id and filename. In detached package, i.e. the metadata file is not within the package, which is most likely for GigaDB, the filename ro-crate-metadata.json is renamed to xxxx-ro-crate-metadata.json, e.g. xxxx can be the dataset ID.

---

## Step 1
- **Initialization of the Crate**: Create the empty crate, then set the profile to specify the context version and the root data entity ID.- **Initialization of the Root Data Entity**: Set ID, name, description, datePublished, i.e. the date of first publication, and sdDatePublished, i.e. the date on which the current structured data was generated or published. The dates are is ISO 8601 standard, e.g. YYYY-MM-DD. For GigaDB, the dataset is most likely to be web-based, the ID has to be an absolute URI, e.g. **[https://gigadb.org/dataset/102736](https://gigadb.org/dataset/102736)**.
- **Specification of the Components**: Specify the ID of the files, dataset such as zip file using hasPart, possibly using the \# directory construct to collectively describe many files. Refer to **[<b>Step 2</b>](#step-2)** for handling the data entities of these files and datasets and potentially any entities derived from them. Note that metadata file and the preview file, if it exists, are specially treated and not included in hasPart.
-  **Specification of the License**: Specify the ID of the license using license, e.g. *[https://creativecommons.org/publicdomain/zero/1.0/](https://creativecommons.org/publicdomain/zero/1.0/)* for the CC0 v1.0 license. Refer to **[<b>Step 3</b>](#step-3)** for handling the contextual entity of the license.
-  **Specification of the Thumbnail**: Specify the ID of the thumbnail using thumbnail. The ID is recommended to be the corresponding downloable PNG. Refer to **[<b>Step 4</b>](#step-4)** for handling the contextual entity of the thumbnail.
-  **Specification of the Publisher and sdPublisher**: Specify the ID of the publisher and sdPublisher using publisher and sdPublisher, e.g. *[https://gigadb.org/](https://gigadb.org/)* for GigaDB being the publisher and sdPublisher. Refer to **[<b>Step 5</b>](#step-5)** for handling the contextual entity of the publisher and sdPublisher.
-  **Specification of the Identifier and Cite-as**:  Specify the identifier of the crate using identifier as an @id. As a special construct, we also include the identifier one by one as a plain string using cite-as. The identifier should be chosen to be persistent and resolvable in this way from a URI, which is commonly possible for a GigaDB dataset that has its doi. For example, it can be **[https://doi.org/10.4225/59/59672c09f4a4b](https://doi.org/10.4225/59/59672c09f4a4b)**. Refer to **[<b>Step 6</b>](#step-6)** for handling the contextual entity of the identifier.
-  **Specification of Extra/Additional Information**: In case there is metadata that cannot be precisely described using existing properties, there is a special construct for it. Specify an exifData using a local identifier such as \#extraInfo as an @id. Refer to **[<b>Step 7</b>](#step-7)** for handling the contextual entity of the exifData. In a GigaDB dataset, information of the root dataset including Dataset type , Additional information , Additional information , Additional information , Additional information , Additional information , Additional information , Additional information , Additional information , Github links , Github links , Github links , Github links , Accessions (data not in GigaDB) and History can be wrapped by this construct. Note that this construct also works for other entities, e.g. Awardee and Award ID used with the organization entity for the funder, or Extra Information used with different file entities.
-  **Specification of Citation**: In case the dataset cites publications like other datasets or papers, we have to include this information by specifying the ID of the publication using citation. Note that the ID has to be a URL (for example a DOI URL). In case of citing another dataset/crate, the ID should be chosen to be the @id value of the identifier property of that crate instead of the actual ID of that crate. Refer to **[<b>Step 8</b>](#step-8)** for handling the contextual entity of the citation of the publication.
-   **Specification of Authors**: Specify the IDs of the author(s) one by one using author. For a GigaDB dataset, ORCID is usually picked as the ID for an author. For example, it may be **[https://orcid.org/0000-0001-9083-6757](https://orcid.org/0000-0001-9083-6757)**. Refer to **[<b>Step 9</b>](#step-9)** for handling the contextual entity of each of the author(s).
-  **Specification of Funders**: Here the assumption that no information about an explicit associated research project is present is made. Specify the ID of the funder using funder, which happens to be the case for some of the GigaDB datasets. For a gigaDB dataset, the ID is often selected to be a ror, for instance, **[https://ror.org/011kf5r70](https://ror.org/011kf5r70)**. Refer to **[<b>Step 10</b>](#step-10)** for handling the contextual entity of the funder.
-  **Specification of Keywords**: Specify the keyword(s) of the root dataset using keywords as a plain string that concatenates all keywords with comma as the delimiter. As a special construct, together with the use of keywords property, we have to specify the IDs of these keyword(s) one by one using about as @id's. Such ID is usually a url that explains the corresponding keyword, for example, **[https://nanoporetech.com/](https://nanoporetech.com/)** for the keyword of oxford nanopore technologies. Refer to **[<b>Step 11</b>](#step-11)** for handling the contextual entity of the about property.

## Step 2
- **File**: Create a File entity with the respective ID, which has to be an absolute URI for a web-based entity. For a GigaDB dataset, it is most likely web-based, the ID is often selected to be the url that directly downloads the file. Then, we set the name, contentSize and encodingFormat. Note that the contentSize is either in kB or MB. Also, note that the encodingFormat is a plain string xxx/yyy, for instance, text/csv. In some cases that a more informative encodingFormat of the form xxx/yyy followed by a **[PRONOM](https://www.nationalarchives.gov.uk/PRONOM/Default.aspx)** identifier, for example, ["application/pdf", {"@id": **["https://www.nationalarchives.gov.uk/PRONOM/fmt/19"]("https://www.nationalarchives.gov.uk/PRONOM/fmt/19")**}]. Additionally, we can include some extra information including data types and file attributes using the exifData construct.
- **Directory/Dataset/zip file**: Create a Dataset entity with the respective ID, which has to be an absolute URI. Such URI should resolve to a listing of the content of the directory/dataset/zip file. For a GigaDB dataset, it is most likely a web-based zip file, the ID is often selected to be the url that shows its description, for example, **[https://gigadb.org/dataset/view/id/102736/Files_page/4](https://gigadb.org/dataset/view/id/102736/Files_page/4)**. Then, we set the name, description, distribution and releaseDate. Note that the distribution is the url that downloads the content, for example, **[https://s3.ap-northeast-1.wasabisys.com/gigadb-datasets/live/pub/10.5524/102001_103000/102736/BoostNano-master.zip](http://127.0.0.1:5501/assets/ro-crate-preview.html#https://s3.ap-northeast-1.wasabisys.com/gigadb-datasets/live/pub/10.5524/102001_103000/102736/BoostNano-master.zip)**. Also, note that the releaseDate should be in the ISO 8601 format. Furthermore, we can include some extra information including data types and file attributes using the exifData construct..
- **Collective Construct with \#**: In case that we prefer describing some files or/and directories collectively, we create a Dataset entity with a local identifier as the ID, for example, \#other-files. Then, we set the name and description.

## Step 3
- **License Creation**: Create a contextual entity with the respective ID of type CreativeWork, then set the name and description of the license, where the description may have to be searched or recorded online.

## Step 4
- **Thumbnail Handling**: When the thumbnail is incidental to the root dataset, usually the case, we do not include it in the hasPart of the root data entity and creates a File entity with the respective ID.

## Step 5
- **Publisher and sdPublisher Handling**: Create an Organization entity with the respective ID, then set the name and description of the organization, where the name and the description may have to be searched or recorded online. Also, set the contactPoint with usually the email following *mailto:*, e.g. **[mailto:database@gigasciencejournal.com](mailto:database@gigasciencejournal.com)**. Then, create a contactPoint entity with this respective ID, and set the contactType, email and identifier. For the case of the example ID, the email and identifier can share a plain string database@gigasciencejournal.com, while the contactType may be a plain string saying the contact of the publisher.

## Step 6
- **Identifier Handling**: Create a contextual entity of type PropertyValue with the respective ID, then set the propertyID, value and url. For example, the propertyID is **[https://registry.identifiers.org/registry/doi](https://registry.identifiers.org/registry/doi)** given the ID of the identifier being a doi. In case of a doi's ID of **[https://doi.org/10.4225/59/59672c09f4a4b](https://doi.org/10.4225/59/59672c09f4a4b)**, the value is set to be a plain string of doi:10.5524/102736. The url is often chosen to be identical to the ID of the identifier.

## Step 7
- **exifData Handling**: Create a contextual entity with the respective ID of type PropertyValue, then set the name and value of the entity, where the name is the property name and the value is the property value as if such property existed in the context.

## Step 8
- **Citation Handling**: We will discuss the two cases when the publication is another dataset and a paper.
-- **Another Dataset/Crate**: Create a Publication entity of type CreativeWork with the respective ID, add an additional type of Dataset. Then, set the property conformsTo to be the version-less generic RO-Crate profile **[https://w3id.org/ro/crate](https://w3id.org/ro/crate)**. Note that we do not set hasPart and usually other properties for the entity representing the another crate, since its content and further metadata is available from its own RO-Crate Metadata Document.
-- **A Paper**: Create a Publication entity of type ScholarlyArticle with the respective ID. then set the name. Also, set the author, identifier, issn, journal, datePublished and creditText, if any. Note that author can has more than one value and datePublished should be in ISO 8601 format.

## Step 9
- **Author Handling**: Create a Person entity with the respective ID, then set the affiliation and the name. The affiliation should refer to an Organization entity. In case that such entity does not exist yet, we create an Organization entity with the respective ID, then set the name, where the name may have to be searched or recorded online. For a GigaDB dataset, a ror is often picked as the ID for the organization, for instance, **[https://ror.org/01ej9dk98](https://ror.org/01ej9dk98)**.

## Step 10
- **Funder Handling**: Create an Organization entity with the respective ID, then set the identifier, name and description. The identifier is always to be the same as the ID, and the description is Funding Body in this case. Additionally, we can use the exifData construct to include the information regarding the Awardee and the Award ID.

## Step 11
- **About Handling**: If the respective ID is an url, we create a contextual entity of type URL with the respective ID and set the name of the entity.

---

## Remark
There are other ways to create a RO-Crate Metadata document for a GigaDB dataset. This only serves as a rather minimal possible way to construct the document, where not all possible metadata of all entities are included. For manipulating an existing metadata document, we can similarly refer to these steps to look for missing parts.

---




