<?php

namespace ProcessMaker\Http\Controllers\Api;

use Illuminate\Http\Request;
use OpenAI\Client;
use ProcessMaker\Ai\Handlers\NlqToCategoryHandler;
use ProcessMaker\Ai\Handlers\NlqToPmqlHandler;
use ProcessMaker\Http\Controllers\Controller;
use ProcessMaker\OpenAI\OpenAIHelper;
use ProcessMaker\Plugins\Collections\Models\Collection;

class OpenAIController extends Controller
{
    public function NLQToPMQL(Client $client, Request $request)
    {
        /**
         * Types: requests, tasks, collections, settings, security_logs
         **/
        $nlqToPmqlHandler = new NlqToPmqlHandler();
        [$result, $usage, $originalQuestion] = $nlqToPmqlHandler->generatePrompt(
            $request->input('type'),
            $request->input('question')
        )->execute();

        return response()->json([
            'result' => $result,
            'usage' => $usage,
            'question' => $originalQuestion,
        ]);
    }

    public function NLQToCategory(Client $client, Request $request)
    {
        $nlqToCategoryHandler = new NlqToCategoryHandler();
        [$type, $classifierUsage, $originalQuestion] = $nlqToCategoryHandler->generatePrompt(null,
            $request->input('question')
        )->execute();

        // Route to the specific prompt
        $nlqToPmqlHandler = new NlqToPmqlHandler();
        [$result, $usage, $originalQuestion] = $nlqToPmqlHandler->generatePrompt(
            $type,
            $originalQuestion
        )->execute();

        // Calc total usage
        $usage->classifierTotalTokens = $classifierUsage->totalTokens;
        $usage->total = $usage->totalTokens + $classifierUsage->totalTokens;

        // Save the response
        $nlqToPmqlHandler->saveResponse($type, $result);

        // If response is json (needed for collections when asking for specific one)
        $resultDecoded = json_decode($result, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Search for collection
            $collection = Collection::where('name', 'like', '%' . mb_strtolower($resultDecoded['collection']) . '%')->first();
            // dd($collection);
        }

        return response()->json([
            'usage' => $usage,
            'result' => $result,
            'question' => $originalQuestion,
            'collection' => isset($collection) ? $collection : null,
        ]);
    }
}
