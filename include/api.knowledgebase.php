<?php
include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.knowledgebase.php';

class KnowledgebaseApiController extends ApiController {

    function get() {
        $kb_array = array();

        $categories = Category::objects()
            ->exclude(Q::any(array(
                'ispublic'=>Category::VISIBILITY_PRIVATE,
                Q::all(array(
                        'faqs__ispublished'=>FAQ::VISIBILITY_PRIVATE,
                        'children__ispublic' => Category::VISIBILITY_PRIVATE,
                        'children__faqs__ispublished'=>FAQ::VISIBILITY_PRIVATE,
                        ))
            )))
            ->annotate(array('faq_count' => SqlAggregate::COUNT(
                            SqlCase::N()
                            ->when(array(
                                    'faqs__ispublished__gt'=> FAQ::VISIBILITY_PRIVATE), 1)
                            ->otherwise(null)
            )))
            ->annotate(array('children_faq_count' => SqlAggregate::COUNT(
                            SqlCase::N()
                            ->when(array(
                                    'children__faqs__ispublished__gt'=> FAQ::VISIBILITY_PRIVATE), 1)
                            ->otherwise(null)
            )));

        if ($categories->exists(true)) {
            foreach ($categories as $C) {


                if (($p=$C->parent)
                        && ($categories->findFirst(array(
                                    'category_id' => $p->getId()))))
                    continue;

                    
                $count = $C->faq_count + $C->children_faq_count;
                $this_category = array(
                    'id' => $C->getId(),
                    'name' => Format::htmlchars($C->getLocalName()),
                    'description' => Format::safe_html($C->getLocalDescriptionWithImages()),
                    'count' => $count,
                    'subcategories' => array(),
                    'faqs' => array()
                );


                if (($subs=$C->getPublicSubCategories())) {
                    foreach ($subs as $c) {
                        $this_subcategories_faqs = array();

                        foreach ($c->faqs
                        ->exclude(array('ispublished'=>FAQ::VISIBILITY_PRIVATE))
                        ->limit(5) as $F) { 
                            array_push($this_subcategories_faqs, array(
                                'id' => $F->getId(),
                                'question' => $F->getLocalQuestion() ?: $F->getQuestion(),
                                'answer' => $F->getLocalAnswer() ?: $F->getAnswer()
                            ));
                        }

                        array_push($this_category['subcategories'], array(
                            'id' => $c->getId(),
                            'name' => Format::htmlchars($c->getLocalName()),
                            'count' => $c->faq_count,
                            'faqs' => $this_subcategories_faqs
                        ));
                    }
                }

                foreach ($C->faqs
                        ->exclude(array('ispublished'=>FAQ::VISIBILITY_PRIVATE))
                        ->limit(5) as $F) { 
                            array_push($this_category['faqs'], array(
                                'id' => $F->getId(),
                                'question' => $F->getLocalQuestion() ?: $F->getQuestion(),
                                'answer' => $F->getLocalAnswer() ?: $F->getAnswer()
                            ));
                }

                array_push($kb_array, $this_category);
            }
        } else {
            array_push($kb_array, 'NO FAQs found');
        }
        
        header('Content-Type: application/json; charset=utf-8');
        return json_encode($kb_array);
    }
}
?>
