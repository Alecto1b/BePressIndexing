<?php

namespace BePressIndexing;

use App\Classes\Plugin;
use App\Facades\Hook;
use App\Facades\MetaTag;
use App\Frontend\Conference\Pages\Paper;
use App\Models\Submission;
use Filament\Panel;

class BePressIndexingPlugin extends Plugin
{
	public function boot()
	{
		if (!app()->getCurrentConference()) return false;

		Hook::add('Frontend::Paper::addMetadata', function ($hookName, $livewire, $paper) {
			$this->addMetadata($paper);
		});
	}

	public function addMetadata(Submission $paper)
	{
		$site = app()->getSite();
		$conference = app()->getCurrentConference();

		MetaTag::add('bepress_citation_title', e($paper->getMeta('title')));

		$paper->authors->each(function ($author) {
			MetaTag::add('bepress_citation_author', $author->fullName);
			if ($author->getMeta('affiliation')) {
				MetaTag::add('bepress_citation_author_institution', e($author->getMeta('affiliation')));
			}
		});

		if ($paper->isPublished()) {
			MetaTag::add('bepress_citation_date', $paper->published_at?->format('Y'));
		}

		if ($paper->doi?->doi) {
			MetaTag::add('bepress_citation_doi', $paper->doi->doi);
		}

		$proceeding = $paper->proceeding;

		MetaTag::add('bepress_citation_series_title', e($conference->name));
		if ($conference->getMeta('issn')) {
			MetaTag::add('bepress_citation_issn', e($conference->getMeta('issn')));
		}

		MetaTag::add('bepress_citation_volume', e($proceeding->volume));
		MetaTag::add('bepress_citation_issue', e($proceeding->number));

		if ($paper->getMeta('article_pages')) {
			[$start, $end] = explode('-', $paper->getMeta('article_pages'));

			if ($start) {
				MetaTag::add('bepress_citation_firstpage', $start);
			}

			if ($end) {
				MetaTag::add('bepress_citation_lastpage', $end);
			}
		}

		MetaTag::add('bepress_citation_abstract_html_url', route(Paper::getRouteName(), ['submission' => $paper->getKey()]));

		$paper->galleys->each(function ($galley) {
			if ($galley->isPdf()) {
				MetaTag::add('bepress_citation_pdf_url', $galley->getUrl());
			}
		});


		collect(explode(PHP_EOL, $paper->getMeta('references')))
			->filter()
			->values()
			->each(fn($reference) => MetaTag::add('citation_reference', $reference));
	}
}
