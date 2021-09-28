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

class SearchCommand extends Command
{
    
    protected function configure()
    {
        $this->setName('reddit:search')
            ->setDescription('Search Reddit.com by Subreddit and Title / Description!')
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
        $httpClient = HttpClient::create([
            'headers'   => [
                'Accept'        => 'application/json',
                'User-Agent'    =>  'RedditApiTest/0.1 by Zarif Rahman'
            ],
        ]);

        $response = $httpClient->request('GET', 'https://www.reddit.com/r/'.$subreddit.'/search.json',[

            'query' => [
                'restrict_sr'   => 'on',
                'sort'          => 'new',
                'q'             => 'title:'.$term,
                'limit'         =>  '100'
            ],
        ]);

        $statusCode = $response->getStatusCode();
        // $statusCode = 200
        $contentType = $response->getHeaders()['content-type'][0];
        // $contentType = 'application/json'
        $content = $response->getContent();
        // $content = '{"id":521583, "name":"symfony-docs", ...}'
        $content = $response->toArray();
        // $content = ['id' => 521583, 'name' => 'symfony-docs', ...]

        $output -> writeln([
            'Reddit Search v0.1.0',
            '==================== ',
        ]);

        $table = new Table($output);
        $table->setHeaders(['Date','Title', 'URL', 'Excerpt']);

        foreach($content['data']['children'] as $key => $array){

            $date = array_column($array,'created_utc');
            $title = array_column($array,'title');
            $url = array_column($array,'permalink');
            $selftext = array_column($array,'selftext');

            $strini = strval($selftext[0]);

            $pos = stripos($strini,$term);
            $len = strlen($strini);
            $termlen = strlen($term);

            // Does contain search term in excerpt
            if($pos == true){
                // MIDDLE WITH 20 CHARS FRONT AND BACK
                if($pos >= 20){
                    $strfin = '...'.substr($strini,($pos-20),($pos-$len)).'<fg=green;options=bold,underscore>'.$term.'</>'.substr($strini,($pos+$termlen),20).'...';
                }

                // less than 20 characters from the end of the excerpt to the search term
                elseif($len-$pos < 20){
                    $strfin = '...'.substr($strini,-1,20);
                }

                // less than 20 characters from the start of the excerpt to the search term
                elseif($pos < 20){
                    $strfin = '<fg=green;options=bold,underscore>'.$term.'</>'.substr($strini,$pos+$termlen,20).'...';
                }
            }
            
            // Does not contain search term in excerpt
            else{
                if($len > 30){
                    $strfin = substr($strini,0,30).'...';
                }
                else{
                    $strfin = substr($strini,0,30);
                }
            }

            if(substr(strval($selftext[0]),0,30) != ''){
                $rows[] = [
                    "date" => date("Y-m-d H:i:s", substr($date[0], 0, 10)),
                    "title" => substr(strval($title[0]),0,30), 
                    "url" => "https://reddit.com".strval($url[0]), 
                    "excerpt" => $strfin
                ];
            }
        }

        usort($rows, function ($a, $b) {
            return $a['title'] <=> $b['title'];
        });

        if(isset    ($rows)){
            $table->setRows($rows);    
            $table->render();
        }

        else {
            $output -> writeln([
                '==================== ',
                'No records found',
            ]);
        }
        
        return is_int($output) ? $output : 0;
    }
}

?>