<?php

if (!defined('ABSPATH')) exit;

class CreateBookXML
{
    public static function generate($bookId, $chapterId = false)
    {
        $d = [
            'post_ID' => $bookId,
            'post_title' => get_the_title($bookId),
            'doi' => get_post_meta($bookId, '_doi', true),
            'jats_abstract' => get_post_meta($bookId, '_jats_abstract', true),
            'isbn_e' => get_post_meta($bookId, '_isbn_e', true),
            'isbn_p' => get_post_meta($bookId, '_isbn_p', true),
            'edition_number' => get_post_meta($bookId, '_edition_number', true),
            'online_publication_date' => get_post_meta($bookId, '_online_publication_date', true),
            'print_publication_date' => get_post_meta($bookId, '_print_publication_date', true),
            'language' => get_post_meta($bookId, '_language', true),
            'resource' => get_post_meta($bookId, '_resource', true),
            'contributors' => carbon_get_post_meta($bookId, 'contributors') ?: [],
        ];

        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;

        // Cria DOI BATCH
        $doiBatch = self::buildDoiBatch($xml, $d);

        // Adiciona HEAD dentro do DOI BATCH
        $head = self::buildHead($xml, $d);
        $doiBatch->appendChild($head);

        // Adiciona BODY dentro do DOI BATCH
        $body = $xml->createElement('body');

        // Livro
        $book = $xml->createElement('book');
        $book->setAttribute('book_type', 'monograph');

        $metadata = self::buildBookMetadata($xml, $d);
        $book->appendChild($metadata);

        $body->appendChild($book);

        // Capítulo
        if ($chapterId) {
            $chapterData = [
                'post_ID' => $chapterId,
                'post_title' => get_the_title($chapterId),
                'doi' => get_post_meta($chapterId, '_doi', true),
                'jats_abstract' => get_post_meta($chapterId, '_jats_abstract', true),
                'isbn_e' => get_post_meta($chapterId, '_isbn_e', true),
                'isbn_p' => get_post_meta($chapterId, '_isbn_p', true),
                'online_publication_date' => get_post_meta($chapterId, '_online_publication_date', true),
                'print_publication_date' => get_post_meta($chapterId, '_print_publication_date', true),
                'language' => get_post_meta($chapterId, '_language', true),
                'resource' => get_post_meta($chapterId, '_resource', true),
                'contributors' => carbon_get_post_meta($chapterId, 'contributors') ?: [],
                'first_page' => get_post_meta($chapterId, '_first_page', true),
                'last_page' => get_post_meta($chapterId, '_last_page', true),
                'component_number' => get_post_meta($chapterId, '_component_number', true),
            ];

            $chapter = self::buildChapter($xml, $chapterData);
            $body->appendChild($chapter);
        }

        $doiBatch->appendChild($body);

        // Adiciona DOI BATCH ao XML
        $xml->appendChild($doiBatch);

        return $xml->saveXML();
    }


    private static function buildDoiBatch(DOMDocument $xml, array $d)
    {
        $root = $xml->createElement('doi_batch');

        $root->setAttribute('xmlns', 'http://www.crossref.org/schema/5.4.0');
        $root->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $root->setAttribute(
            'xsi:schemaLocation',
            'http://www.crossref.org/schema/5.4.0 https://www.crossref.org/schemas/crossref5.4.0.xsd'
        );
        $root->setAttribute('xmlns:jats', 'http://www.ncbi.nlm.nih.gov/JATS1');
        $root->setAttribute('xmlns:fr', 'http://www.crossref.org/fundref.xsd');
        $root->setAttribute('xmlns:mml', 'http://www.w3.org/1998/Math/MathML');
        $root->setAttribute('version', '5.4.0');

        return $root;
    }

    // ----------------------------------------------------------------------
    // HEAD
    // ----------------------------------------------------------------------

    private static function buildHead(DOMDocument $xml, array $d)
    {
        $head = $xml->createElement('head');

        $batchId = 'arcoed-' . $d['post_ID'] . '-' . date('YmdHis');

        $head->appendChild($xml->createElement('doi_batch_id', $batchId));
        $head->appendChild($xml->createElement('timestamp', date('YmdHis')));

        // Depositor
        $depositor = $xml->createElement('depositor');
        $depositor->appendChild($xml->createElement('depositor_name', 'Arco Editores'));
        $depositor->appendChild($xml->createElement('email_address', 'contato@arcoeditores.com'));
        $head->appendChild($depositor);

        $head->appendChild($xml->createElement('registrant', 'Arco Editores'));

        return $head;
    }


    // ----------------------------------------------------------------------
    // BOOK METADATA
    // ----------------------------------------------------------------------

    private static function buildBookMetadata(DOMDocument $xml, array $d)
    {
        $meta = $xml->createElement('book_metadata');
        $meta->setAttribute('language', $d['language']);

        $meta->appendChild(self::buildContributors($xml, $d['contributors']));
        $meta->appendChild(self::buildTitles($xml, $d['post_title']));
        $meta->appendChild(self::buildAbstract($xml, $d));
        $meta->appendChild($xml->createElement('edition_number', $d['edition_number']));

        if (!empty($d['online_publication_date'])) {
            $online = new DateTime($d['online_publication_date']);
            $meta->appendChild(self::buildPubDate($xml, $online, 'online'));
        }

        if (!empty($d['print_publication_date'])) {
            $print = new DateTime($d['print_publication_date']);
            $meta->appendChild(self::buildPubDate($xml, $print, 'print'));
        }

        if (!empty($d['isbn_e'])) {
            $meta->appendChild(self::buildIsbn($xml, $d['isbn_e'], 'electronic'));
        }

        if (!empty($d['isbn_p'])) {
            $meta->appendChild(self::buildIsbn($xml, $d['isbn_p'], 'print'));
        }

        $meta->appendChild(self::buildPublisher($xml, 'Arco Editores'));
        $meta->appendChild(self::buildDoiData($xml, $d['doi'], $d['resource']));

        return $meta;
    }

    // ----------------------------------------------------------------------
    // CONTRIBUTORS
    // ----------------------------------------------------------------------

    private static function buildContributors(DOMDocument $xml, array $lista)
    {
        $contributors = $xml->createElement('contributors');

        foreach ($lista as $i => $c) {
            $sequence = ($i === 0) ? 'first' : 'additional';

            $given = trim($c['given'] ?? '');
            $surname = trim($c['surname'] ?? '');
            $role = $c['role'] ?? 'author';

            // ORGANIZAÇÃO — não tem surname
            if ($surname === '') {
                if ($given === '') {
                    continue; // sem nome, ignora
                }

                $org = $xml->createElement('organization', $given);
                $org->setAttribute('contributor_role', $role);
                $org->setAttribute('sequence', $sequence);

                $contributors->appendChild($org);
                continue;
            }

            // PESSOA
            $person = $xml->createElement('person_name');
            $person->setAttribute('contributor_role', $role);
            $person->setAttribute('sequence', $sequence);

            if ($given !== '') {
                $person->appendChild($xml->createElement('given_name', $given));
            }
            $person->appendChild($xml->createElement('surname', $surname));

            // Afiliacoes
            if (!empty($c['afiliacoes'])) {
                $affs = $xml->createElement('affiliations');
                foreach ($c['afiliacoes'] as $inst) {
                    if (trim($inst) !== '') {
                        $instNode = $xml->createElement('institution');
                        $instNode->appendChild($xml->createElement('institution_name', $inst));
                        $affs->appendChild($instNode);
                    }
                }
                if ($affs->childNodes->length > 0) {
                    $person->appendChild($affs);
                }
            }

            // ORCID
            if (!empty($c['orcid'])) {
                $person->appendChild($xml->createElement('ORCID', $c['orcid']));
            }

            $contributors->appendChild($person);
        }

        return $contributors;
    }


    // ----------------------------------------------------------------------
    // TITLES
    // ----------------------------------------------------------------------

    private static function buildTitles(DOMDocument $xml, string $title)
    {
        $titles = $xml->createElement('titles');
        $titles->appendChild($xml->createElement('title', $title));
        return $titles;
    }

    // ----------------------------------------------------------------------
    // ABSTRACT
    // ----------------------------------------------------------------------

    private static function buildAbstract(DOMDocument $xml, array $d)
    {
        $abstract = $xml->createElement('jats:abstract');

        // Pega o resumo do array
        $textoResumo = $d['jats_abstract'] ?? '';

        // Adiciona parágrafo apenas se houver texto
        if ($textoResumo !== '') {
            $p = $xml->createElement('jats:p', $textoResumo);
            $abstract->appendChild($p);
        }

        return $abstract;
    }


    // ----------------------------------------------------------------------
    // PUBLICATION DATE
    // ----------------------------------------------------------------------

    private static function buildPubDate(DOMDocument $xml, DateTime $date, string $type)
    {
        $pub = $xml->createElement('publication_date');
        $pub->setAttribute('media_type', $type);

        $pub->appendChild($xml->createElement('month', $date->format('m')));
        $pub->appendChild($xml->createElement('day', $date->format('d')));
        $pub->appendChild($xml->createElement('year', $date->format('Y')));

        return $pub;
    }

    // ----------------------------------------------------------------------
    // ISBN
    // ----------------------------------------------------------------------

    private static function buildIsbn(DOMDocument $xml, string $isbn, string $type)
    {
        $node = $xml->createElement('isbn', $isbn);
        $node->setAttribute('media_type', $type);
        return $node;
    }

    // ----------------------------------------------------------------------
    // PUBLISHER
    // ----------------------------------------------------------------------

    private static function buildPublisher(DOMDocument $xml, string $publisherName)
    {
        $publisher = $xml->createElement('publisher');
        $publisher->appendChild($xml->createElement('publisher_name', $publisherName));
        return $publisher;
    }

    // ----------------------------------------------------------------------
    // DOI DATA
    // ----------------------------------------------------------------------

    private static function buildDoiData(DOMDocument $xml, string $doi, string $resource)
    {
        $doiData = $xml->createElement('doi_data');
        $doiData->appendChild($xml->createElement('doi', $doi));

        if (!empty($resource)) {
            $doiData->appendChild($xml->createElement('resource', $resource));
        }

        return $doiData;
    }

    // ----------------------------------------------------------------------
    // CHAPTER
    // ----------------------------------------------------------------------
    private static function buildChapter(DOMDocument $xml, array $d)
    {
        $chapter = $xml->createElement('content_item');
        $chapter->setAttribute('component_type', 'chapter');
        $chapter->setAttribute('publication_type', 'full_text');
        $chapter->setAttribute('language', $d['language'] ?? 'pt');

        // Contributors
        $chapter->appendChild(self::buildContributors($xml, $d['contributors']));

        // Titles
        $chapter->appendChild(self::buildTitles($xml, $d['post_title']));

        // Abstract
        $chapter->appendChild(self::buildAbstract($xml, $d));

        // Component number
        if (!empty($d['component_number'])) {
            $chapter->appendChild($xml->createElement('component_number', $d['component_number']));
        }

        // Publication date
        if (!empty($d['print_publication_date'])) {
            $pubDate = new DateTime($d['print_publication_date']);
            $chapter->appendChild(self::buildPubDate($xml, $pubDate, $d['publication_type'] ?? 'print'));
        }
        // Publication date
        if (!empty($d['online_publication_date'])) {
            $pubDate = new DateTime($d['online_publication_date']);
            $chapter->appendChild(self::buildPubDate($xml, $pubDate, $d['publication_type'] ?? 'online'));
        }

        // Pages
        if (!empty($d['first_page']) || !empty($d['last_page'])) {
            $pages = $xml->createElement('pages');
            if (!empty($d['first_page'])) {
                $pages->appendChild($xml->createElement('first_page', $d['first_page']));
            }
            if (!empty($d['last_page'])) {
                $pages->appendChild($xml->createElement('last_page', $d['last_page']));
            }
            $chapter->appendChild($pages);
        }

        // DOI
        if (!empty($d['doi'])) {
            $chapter->appendChild(self::buildDoiData($xml, $d['doi'], $d['resource'] ?? ''));
        }

        return $chapter;
    }
}
