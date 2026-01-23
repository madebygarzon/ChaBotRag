<?php
/**
 * Bricks Content Extractor Service
 *
 * @package AIChatbotRAG
 */

namespace AIChatbotRAG\Services;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class BricksContentExtractor
 * Extracts content from Bricks Builder elements including accordions, tabs, etc.
 */
class BricksContentExtractor {

    /**
     * Extract content from Bricks elements for a post
     */
    public function extractContent(int $postId): string {
        $content = '';
        
        try {
            // Get Bricks data from post meta
            $bricksData = get_post_meta($postId, '_bricks_data', true);
            
            if ($bricksData && is_array($bricksData)) {
                // Extract from Bricks data structure
                $content .= $this->extractFromBricksData($bricksData);
            } else {
                // Fallback: Get rendered HTML content using regex extraction
                $htmlContent = $this->getPostRenderedHTML($postId);
                if ($htmlContent) {
                    $content .= $this->extractFromHTMLRegex($htmlContent);
                }
            }
        } catch (\Exception $e) {
            error_log('BricksContentExtractor Error for post ' . $postId . ': ' . $e->getMessage());
            // Fallback to basic content
            $post = get_post($postId);
            if ($post) {
                $content = $post->post_title . "\n\n" . $post->post_content;
            }
        }

        return $content;
    }

    /**
     * Extract content from Bricks data array
     */
    private function extractFromBricksData(array $bricksData): string {
        $content = '';
        
        if (empty($bricksData)) {
            return $content;
        }

        // Extract from different element types
        foreach ($bricksData as $element) {
            if (!is_array($element)) {
                continue;
            }

            $elementType = $element['name'] ?? '';
            
            switch ($elementType) {
                case 'accordion':
                    $content .= $this->extractAccordionContent($element);
                    break;
                case 'tabs':
                    $content .= $this->extractTabsContent($element);
                    break;
                case 'heading':
                    if (!empty($element['settings']['text'])) {
                        $content .= "\n" . strip_tags($element['settings']['text']) . "\n";
                    }
                    break;
                case 'text':
                    if (!empty($element['settings']['text'])) {
                        $content .= "\n" . strip_tags($element['settings']['text']) . "\n";
                    }
                    break;
                case 'container':
                case 'block':
                    if (!empty($element['children'])) {
                        $content .= $this->extractFromBricksData($element['children']);
                    }
                    break;
            }
        }

        return $content;
    }

    /**
     * Extract content from accordion elements
     */
    private function extractAccordionContent(array $accordion): string {
        $content = '';
        
        if (empty($accordion['children'])) {
            return $content;
        }

        foreach ($accordion['children'] as $item) {
            if ($item['name'] !== 'accordion-item' || empty($item['children'])) {
                continue;
            }

            $question = '';
            $answer = '';
            
            foreach ($item['children'] as $child) {
                if ($child['name'] === 'accordion-title' && !empty($child['settings']['text'])) {
                    $question = strip_tags($child['settings']['text']);
                } elseif ($child['name'] === 'accordion-content' && !empty($child['children'])) {
                    $itemContent = $this->extractFromBricksData($child['children']);
                    $answer = strip_tags($itemContent);
                }
            }
            
            if (!empty($question) && !empty($answer)) {
                $content .= "\nQ: " . $question . "\n";
                $content .= "A: " . $answer . "\n\n";
            }
        }

        return $content;
    }

    /**
     * Extract content from tabs elements
     */
    private function extractTabsContent(array $tabs): string {
        $content = '';
        
        if (empty($tabs['children'])) {
            return $content;
        }

        foreach ($tabs['children'] as $tab) {
            if ($tab['name'] !== 'tab' || empty($tab['children'])) {
                continue;
            }

            $tabTitle = '';
            $tabContent = '';
            
            foreach ($tab['children'] as $child) {
                if ($child['name'] === 'tab-title' && !empty($child['settings']['text'])) {
                    $tabTitle = strip_tags($child['settings']['text']);
                } elseif ($child['name'] === 'tab-content' && !empty($child['children'])) {
                    $itemContent = $this->extractFromBricksData($child['children']);
                    $tabContent = strip_tags($itemContent);
                }
            }
            
            if (!empty($tabTitle) && !empty($tabContent)) {
                $content .= "\n" . $tabTitle . ":\n";
                $content .= $tabContent . "\n\n";
            }
        }

        return $content;
    }

    /**
     * Get rendered HTML of a post
     */
    private function getPostRenderedHTML(int $postId): string {
        try {
            // Get post content through WordPress filters
            $post = get_post($postId);
            if (!$post) {
                return '';
            }

            // Apply WordPress content filters
            $content = apply_filters('the_content', $post->post_content);
            
            return $content;
            
        } catch (\Exception $e) {
            error_log('BricksContentExtractor HTML fetch error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Extract content from rendered HTML using regex (fallback when DOM is not available)
     */
    private function extractFromHTMLRegex(string $html): string {
        $content = '';
        
        if (empty($html)) {
            return $content;
        }

        try {
            // Extract accordions - targeting your specific structure
            $accordionPattern = '/<ul[^>]*class="[^"]*brxe-accordion[^"]*"[^>]*>(.*?)<\/ul>/is';
            if (preg_match_all($accordionPattern, $html, $accordionMatches)) {
                foreach ($accordionMatches[1] as $accordionHtml) {
                    $itemPattern = '/<li[^>]*class="[^"]*accordion-item[^"]*"[^>]*>(.*?)<\/li>/is';
                    if (preg_match_all($itemPattern, $accordionHtml, $itemMatches)) {
                        foreach ($itemMatches[1] as $itemHtml) {
                            // Extract title
                            $titlePattern = '/<h3[^>]*class="[^"]*title[^"]*"[^>]*>(.*?)<\/h3>/is';
                            if (preg_match($titlePattern, $itemHtml, $titleMatch)) {
                                $question = strip_tags(html_entity_decode($titleMatch[1], ENT_QUOTES, 'UTF-8'));
                                
                                // Extract content
                                $contentPattern = '/<div[^>]*class="[^"]*accordion-content-wrapper[^"]*"[^>]*>(.*?)<\/div>/is';
                                if (preg_match($contentPattern, $itemHtml, $contentMatch)) {
                                    $answer = strip_tags(html_entity_decode($contentMatch[1], ENT_QUOTES, 'UTF-8'));
                                    
                                    if (!empty($question) && !empty($answer)) {
                                        $content .= "\nQ: " . trim($question) . "\n";
                                        $content .= "A: " . trim($answer) . "\n\n";
                                    }
                                }
                            }
                        }
                    }
                }
            }
            
            // Generic accordion extraction (for other builders)
            $genericAccordionPattern = '/<div[^>]*class="[^"]*accordion[^"]*"[^>]*>(.*?)<\/div>/is';
            if (preg_match_all($genericAccordionPattern, $html, $genericMatches)) {
                foreach ($genericMatches[1] as $accordionHtml) {
                    $genericItemPattern = '/<div[^>]*class="[^"]*accordion-item[^"]*"[^>]*>(.*?)<\/div>/is';
                    if (preg_match_all($genericItemPattern, $accordionHtml, $itemMatches)) {
                        foreach ($itemMatches[1] as $itemHtml) {
                            $genericTitlePattern = '/<[^>]*class="[^"]*title[^"]*"[^>]*>(.*?)<\/[^>]*>/is';
                            $genericContentPattern = '/<[^>]*class="[^"]*content[^"]*"[^>]*>(.*?)<\/[^>]*>/is';
                            
                            if (preg_match($genericTitlePattern, $itemHtml, $titleMatch) && 
                                preg_match($genericContentPattern, $itemHtml, $contentMatch)) {
                                $question = strip_tags(html_entity_decode($titleMatch[1], ENT_QUOTES, 'UTF-8'));
                                $answer = strip_tags(html_entity_decode($contentMatch[1], ENT_QUOTES, 'UTF-8'));
                                
                                if (!empty($question) && !empty($answer)) {
                                    $content .= "\nQ: " . trim($question) . "\n";
                                    $content .= "A: " . trim($answer) . "\n\n";
                                }
                            }
                        }
                    }
                }
            }
            
            // Extract from headings and following content
            $headingPattern = '/<h[1-6][^>]*>(.*?)<\/h[1-6]>/is';
            if (preg_match_all($headingPattern, $html, $headingMatches)) {
                foreach ($headingMatches[0] as $headingHtml) {
                    $headingText = strip_tags(html_entity_decode($headingHtml, ENT_QUOTES, 'UTF-8'));
                    if (!empty($headingText)) {
                        $content .= "\n" . trim($headingText) . "\n";
                        
                        // Look for following paragraph
                        $afterHeading = substr($html, strpos($html, $headingHtml) + strlen($headingHtml));
                        $paragraphPattern = '/<p[^>]*>(.*?)<\/p>/is';
                        if (preg_match($paragraphPattern, $afterHeading, $paragraphMatch)) {
                            $paragraphText = strip_tags(html_entity_decode($paragraphMatch[1], ENT_QUOTES, 'UTF-8'));
                            if (!empty($paragraphText)) {
                                $content .= $paragraphText . "\n\n";
                            }
                        }
                    }
                }
            }
            
        } catch (\Exception $e) {
            error_log('BricksContentExtractor HTML regex parsing error: ' . $e->getMessage());
            // Fallback to simple text extraction
            $content = strip_tags($html);
        }

        return $content;
    }
}