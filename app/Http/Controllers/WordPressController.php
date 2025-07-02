<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache; // Opcional: para cachear las imágenes y evitar peticiones repetidas

class WordPressController extends Controller
{
    public function getPosts()
    {
        $wordpressApiBaseUrl = 'https://tecnologia.iniap.gob.ec/wp-json/wp/v2/';
        $postsEndpoint = $wordpressApiBaseUrl . 'posts';

        try {
            // Fetch posts from WordPress API
            $response = Http::withOptions([
                'verify' => false, // ¡ADVERTENCIA: SOLO PARA DESARROLLO! QUITAR EN PRODUCCIÓN.
            ])->get($postsEndpoint);

            if ($response->successful()) {
                $posts = $response->json();

                // Process each post to get the featured image URL
                foreach ($posts as &$post) { // Use & to modify the original array elements
                    if (isset($post['featured_media']) && $post['featured_media'] > 0) {
                        $mediaId = $post['featured_media'];
                        $mediaEndpoint = $wordpressApiBaseUrl . 'media/' . $mediaId;

                        // Opcional: Cachear la URL de la imagen destacada para evitar peticiones repetidas
                        $imageUrl = Cache::remember("wordpress_featured_image_{$mediaId}", 60 * 24, function () use ($mediaEndpoint) { // Cache por 24 horas
                            try {
                                $mediaResponse = Http::withOptions([
                                    'verify' => false, // ¡ADVERTENCIA: SOLO PARA DESARROLLO! QUITAR EN PRODUCCIÓN.
                                ])->get($mediaEndpoint);

                                if ($mediaResponse->successful()) {
                                    $media = $mediaResponse->json();
                                    // La URL de la imagen principal suele estar en 'source_url'
                                    // o en 'media_details.sizes.full.source_url' o 'media_details.sizes.large.source_url'
                                    // Adaptar según la estructura exacta que recibas del endpoint /media/{id}
                                    if (isset($media['source_url'])) {
                                        return $media['source_url'];
                                    } elseif (isset($media['media_details']['sizes']['full']['source_url'])) {
                                        return $media['media_details']['sizes']['full']['source_url'];
                                    } elseif (isset($media['media_details']['sizes']['large']['source_url'])) {
                                        return $media['media_details']['sizes']['large']['source_url'];
                                    }
                                }
                            } catch (\Exception $e) {
                                // Log the error but don't stop the process
                                \Log::error("Failed to fetch featured media for ID {$mediaId}: " . $e->getMessage());
                            }
                            return null; // Return null if image not found or error occurred
                        });

                        $post['featured_image_src'] = $imageUrl;
                    } else {
                        $post['featured_image_src'] = null; // No featured image
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
