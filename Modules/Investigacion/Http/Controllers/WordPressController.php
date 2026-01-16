<?php

namespace Modules\Investigacion\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WordPressController extends Controller
{
    public function getPosts()
    {
        $wordpressApiBaseUrl = 'https://tecnologia.iniap.gob.ec/wp-json/wp/v2/';
        $postsEndpoint = $wordpressApiBaseUrl . 'posts?per_page=3';

        try {
            $response = Http::withOptions([
                'verify' => false,
            ])->get($postsEndpoint);

            if ($response->successful()) {
                $posts = $response->json();

                foreach ($posts as &$post) {
                    $post['featured_image_src'] = null;
                    if (isset($post['featured_media']) && $post['featured_media'] > 0) {
                        $mediaId = $post['featured_media'];
                        $mediaEndpoint = $wordpressApiBaseUrl . 'media/' . $mediaId;

                        $imageUrl = Cache::remember("wordpress_featured_image_{$mediaId}", 60 * 24, function () use ($mediaEndpoint, $mediaId) {
                            try {
                                $mediaResponse = Http::withOptions([
                                    'verify' => false,
                                ])->get($mediaEndpoint);

                                if ($mediaResponse->successful()) {
                                    $media = $mediaResponse->json();
                                    $url = null;
                                    if (isset($media['media_details']['sizes']['full']['source_url'])) {
                                        $url = $media['media_details']['sizes']['full']['source_url'];
                                    } elseif (isset($media['media_details']['sizes']['large']['source_url'])) {
                                        $url = $media['media_details']['sizes']['large']['source_url'];
                                    } elseif (isset($media['source_url'])) {
                                        $url = $media['source_url'];
                                    }

                                    // Validar que la URL no sea nula y contenga el dominio esperado
                                    if ($url && str_contains($url, 'tecnologia.iniap.gob.ec')) {
                                        return $url;
                                    }
                                }
                            } catch (\Exception $e) {
                                Log::error("Failed to fetch featured media for ID {$mediaId}: " . $e->getMessage());
                            }
                            return null;
                        });

                        $post['featured_image_src'] = $imageUrl;
                    }
                }
                return response()->json($posts);
            } else {
                return response()->json(['error' => 'No se pudieron obtener las publicaciones de WordPress.', 'details' => $response->body()], $response->status());
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error al conectar con la API de WordPress: ' . $e->getMessage()], 500);
        }
    }
}
