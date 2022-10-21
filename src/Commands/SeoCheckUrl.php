<?php

namespace Vormkracht10\Seo\Commands;

use Illuminate\Console\Command;
use Vormkracht10\Seo\Facades\Seo;

class SeoCheckUrl extends Command
{
    public $signature = 'seo:check-url {url}';

    public $description = 'Check the SEO score of a url';

    public function handle(): int
    {
        $score = Seo::check($this->argument('url'));

        $this->info($this->argument('url') . ' - ' . $score->getScore() . '%');

        $this->info('Done!');

        return self::SUCCESS;
    }
}