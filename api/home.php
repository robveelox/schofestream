<?php
declare(strict_types=1);
require __DIR__ . '/../includes/bootstrap.php';
require_auth_api();

$userId = jf_user_id();
$fields = jf_item_fields();
$limit = max(8, min(30, (int)($config['home_row_limit'] ?? 18)));
$prefs = sf_preferences();
$warnings = [];

$safe = static function (string $label, string $path, array $query = [], int $ttl = 20) use (&$warnings): array {
    try { return jf_cached_request($path, $query, $ttl); }
    catch (Throwable) { $warnings[] = $label; return []; }
};

$continueCandidates = $safe('Continue Watching', "/Users/{$userId}/Items", [
    'IncludeItemTypes'=>'Movie,Episode','Recursive'=>'true','IsPlayed'=>'false','Limit'=>120,
    'SortBy'=>'DatePlayed','SortOrder'=>'Descending','Fields'=>$fields,'EnableImages'=>'true',
    'ImageTypeLimit'=>1,'EnableUserData'=>'true','EnableTotalRecordCount'=>'false',
]);
$nextUp = $safe('Next Up', '/Shows/NextUp', [
    'UserId'=>$userId,'Limit'=>$limit,'Fields'=>$fields,'EnableImages'=>'true','ImageTypeLimit'=>1,
    'EnableUserData'=>'true','EnableResumable'=>'true','EnableTotalRecordCount'=>'false',
]);
$latestMovies = $safe('Recently Added Movies', "/Users/{$userId}/Items/Latest", [
    'Limit'=>$limit,'IncludeItemTypes'=>'Movie','Fields'=>$fields,'EnableImages'=>'true','ImageTypeLimit'=>1,'EnableUserData'=>'true',
]);
$latestShows = $safe('Recently Added TV', "/Users/{$userId}/Items/Latest", [
    'Limit'=>$limit,'IncludeItemTypes'=>'Series','Fields'=>$fields,'EnableImages'=>'true','ImageTypeLimit'=>1,'EnableUserData'=>'true',
]);
$favorites = $safe('My List', "/Users/{$userId}/Items", [
    'IncludeItemTypes'=>'Movie,Series','Recursive'=>'true','Filters'=>'IsFavorite','Limit'=>$limit,
    'SortBy'=>'DatePlayed,SortName','SortOrder'=>'Descending','Fields'=>$fields,'EnableImages'=>'true',
    'ImageTypeLimit'=>1,'EnableUserData'=>'true','EnableTotalRecordCount'=>'false',
]);
$collections = $safe('Collections', "/Users/{$userId}/Items", [
    'IncludeItemTypes'=>'BoxSet','Recursive'=>'true','Limit'=>12,'SortBy'=>'SortName','SortOrder'=>'Ascending',
    'Fields'=>$fields,'EnableImages'=>'true','ImageTypeLimit'=>1,'EnableUserData'=>'true','EnableTotalRecordCount'=>'false',
], 45);
$genreResult = $safe('Genres', '/Genres', [
    'UserId'=>$userId,'IncludeItemTypes'=>'Movie,Series','Limit'=>12,'SortBy'=>'SortName','SortOrder'=>'Ascending',
    'EnableImages'=>'false','EnableTotalRecordCount'=>'false',
], 60);

$historyResult = [];
if (sf_home_row_visible($prefs, 'recentlyWatched') || sf_home_row_visible($prefs, 'becauseYouWatched') || sf_home_row_visible($prefs, 'genres')) {
    $historyResult = $safe('Recently Watched', "/Users/{$userId}/Items", [
        'IncludeItemTypes'=>'Movie,Episode','Recursive'=>'true','Limit'=>80,'SortBy'=>'DatePlayed','SortOrder'=>'Descending',
        'Fields'=>$fields,'EnableImages'=>'true','ImageTypeLimit'=>1,'EnableUserData'=>'true','EnableTotalRecordCount'=>'false',
    ], 20);
}

$normalizeLatest = static fn(array $result): array => jf_normalize_items(array_is_list($result) ? $result : (array)($result['Items'] ?? []));
$resumeItems = array_values(array_filter(jf_normalize_items((array)($continueCandidates['Items'] ?? [])), static fn(array $i): bool => (float)($i['positionSeconds'] ?? 0)>0 && empty($i['played'])));
$resumeItems = array_slice($resumeItems,0,$limit);
$movieItems = $normalizeLatest($latestMovies);
$showItems = $normalizeLatest($latestShows);
$nextItems = jf_normalize_items((array)($nextUp['Items'] ?? []));
$favoriteItems = jf_normalize_items((array)($favorites['Items'] ?? []));
$collectionItems = jf_normalize_items((array)($collections['Items'] ?? []));
$historyItems = array_values(array_filter(jf_normalize_items((array)($historyResult['Items'] ?? [])), static fn(array $i): bool => !empty($i['lastPlayedDate'])));
usort($historyItems, static fn(array $a,array $b): int => strcmp((string)($b['lastPlayedDate']??''),(string)($a['lastPlayedDate']??'')));
$historyItems = array_slice($historyItems,0,$limit);

$genres = array_values(array_filter(array_map(static function($g): ?array {
    $name=trim((string)($g['Name']??'')); return $name===''?null:['id'=>(string)($g['Id']??''),'name'=>$name];
}, (array)($genreResult['Items'] ?? []))));
$genreCounts=[];
foreach($historyItems as $watched){
    foreach((array)($watched['genres']??[]) as $genreName){
        $genreName=trim((string)$genreName); if($genreName!=='') $genreCounts[$genreName]=($genreCounts[$genreName]??0)+1;
    }
}
arsort($genreCounts);
$preferredGenreNames=array_slice(array_keys($genreCounts),0,2);
$preferredGenres=[];
foreach($preferredGenreNames as $name) $preferredGenres[]=['id'=>'','name'=>$name];
if(!$preferredGenres) $preferredGenres=array_slice($genres,0,2);
$genreRows=[];
foreach($preferredGenres as $genre){
    $result=$safe('Genre '.$genre['name'], "/Users/{$userId}/Items", [
        'IncludeItemTypes'=>'Movie,Series','Recursive'=>'true','Genres'=>$genre['name'],'Limit'=>$limit,'SortBy'=>'Random',
        'Fields'=>$fields,'EnableImages'=>'true','ImageTypeLimit'=>1,'EnableUserData'=>'true','EnableTotalRecordCount'=>'false',
    ],30);
    $items=jf_normalize_items((array)($result['Items']??[])); if($items)$genreRows[]=['name'=>$genre['name'],'items'=>$items];
}

$because = ['source'=>null,'items'=>[]];
if (sf_home_row_visible($prefs, 'becauseYouWatched') && $historyItems) {
    $source = $historyItems[0];
    $similarSourceId = ($source['type'] === 'Episode' && !empty($source['seriesId'])) ? (string)$source['seriesId'] : (string)$source['id'];
    $similar = $safe('Recommendations', '/Items/' . rawurlencode($similarSourceId) . '/Similar', [
        'UserId'=>$userId,'Limit'=>$limit,'Fields'=>$fields,
    ],45);
    $similarItems = array_values(array_filter(jf_normalize_items((array)($similar['Items']??[])), static fn(array $i): bool => in_array($i['type'],['Movie','Series'],true)));
    if ($similarItems) $because=['source'=>$source,'items'=>$similarItems];
}

$heroCandidates=array_merge($movieItems,$showItems,$favoriteItems); $hero=null;
foreach($heroCandidates as $c){if(!empty($c['backdrop'])&&empty($c['played'])){$hero=$c;break;}}
if(!$hero){foreach($heroCandidates as $c){if(!empty($c['backdrop'])){$hero=$c;break;}}}
$hero ??= ($movieItems[0]??$showItems[0]??null);

json_response([
    'hero'=>$hero,'continueWatching'=>$resumeItems,'nextUp'=>$nextItems,'recentMovies'=>$movieItems,'recentShows'=>$showItems,
    'recentlyWatched'=>sf_home_row_visible($prefs, 'recentlyWatched')?$historyItems:[],'becauseYouWatched'=>$because,
    'favorites'=>$favoriteItems,'collections'=>$collectionItems,'genres'=>$genres,'genreRows'=>$genreRows,
    'preferences'=>$prefs,'partial'=>!empty($warnings),'unavailableRows'=>array_values(array_unique($warnings)),
]);
