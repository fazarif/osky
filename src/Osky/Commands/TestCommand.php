<?php

namespace Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;

use Symfony\Component\HttpClient\HttpClient;

/**
 * Class HelloCommand
 *
 * @package Osky\Commands\Command
 */

class TestCommand extends Command
{
    

    protected function configure()
    {
        $this->setName('reddit:test')
            ->setDescription('Prints Hello-World!')
            ->setHelp('Demonstration of custom commands created by Symfony Console component.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Prompt question

        $helper = $this->getHelper('question');

        $question = new Question('Please enter the name of the subreddit (default: webdev):', 'webdev');
        $subreddit = strtolower($helper->ask($input, $output, $question));

        $search = new Question('Please enter the search term (default: php):', 'php');
        $term = strtolower($helper->ask($input, $output, $search));

        // HTTP Client
        // https://www.reddit.com/r/webdev/search.json?sort=new&restrict_sr=on&q=title:php

        $httpClient = HttpClient::create();

        $response = $httpClient->request('GET', 'https://www.reddit.com/r/webdev/search.json?sort=new&restrict_sr=on&q=title:php&limit=5');

        $statusCode = $response->getStatusCode();
        // $statusCode = 200
        $contentType = $response->getHeaders()['content-type'][0];
        // $contentType = 'application/json'
        $content = $response->getContent();
        // $content = '{"id":521583, "name":"symfony-docs", ...}'
        $content = $response->toArray();
        // $content = ['id' => 521583, 'name' => 'symfony-docs', ...]

        // $output -> writeln([
        //     'Reddit Search v0.1.0',
        //     '==================== ',
        //     ''.$subreddit.$term,
        // ]);

        $table = new Table($output);
        $table->setHeaders(['Date','Title', 'URL', 'Excerpt']);

        foreach($content['data']['children'] as $key => $array){

            $date = array_column($array,'created_utc');
            $title = array_column($array,'title');
            $url = array_column($array,'permalink');
            $selftext = array_column($array,'selftext');

            $pos = strpos($selftext,'php');

            if(substr(strval($selftext[0]),0,30) != ''){
                $rows[] = [
                    date("Y-m-d H:i:s", substr($date[0], 0, 10)),
                    substr(strval($title[0]),0,30), 
                    "https://reddit.com".strval($url[0]), 
                    substr(strval($selftext[0]),0,30)
                ];
            }

        }

        $table->setRows($rows);

        // foreach ($titles_all as $key => $value){
        //     echo $value[0];
        // }

        // print_r($date_all);

        $table->render();
        
        return is_int($output) ? $output : 0;
    }
}

?>