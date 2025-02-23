<?php
public function updateRdtEntiesg(Request $request) {
    // Get all keys
    $keys = array_keys($request->all());
    $tokenindex = array_search("_token", $keys);
    $imgIndex = array_search("img", $keys);
    $rdtIndex = array_search("rdtType", $keys);
    $lotNumberIndex = array_search("lotNumber", $keys);

    unset($keys[$tokenindex], $keys[$imgIndex], $keys[$rdtIndex], $keys[$lotNumberIndex]);

    $rdtType = $request->get('rdtType');
    $lotNumber = $request->get('lotNumber');

    // Initialize filePath
    $filePath = null;

    // Check if 'img' is present in the request
    if ($request->hasFile('img')) {
        $fileName = time() . '_' . $request->img->getClientOriginalName();
        $filePath = $request->file('img')->storeAs('uploads', $fileName, 'public');
    }

    // Calculation logic
    $A = $gp36 ? 1 : 0;
    $B = $gp140 ? 2 : 0;
    $C = $p31 ? 1 : 0;
    $D = $gp160 ? 2 : 0;
    $E = $p24 ? 4 : 0;
    $F = $gp41 ? 8 : 0;

    $H2_code = $A + $B;
    $H1_Code = $C + $D + $E + $F;

    $summary_code = "{$H2_code} - {$H1_Code}";
   Log::info("Summary Code", ['summary_code' => $summary_code]);

    // Lookup mapping
    $result_lookup = [
        "0 - 0"=> "HIV Antibody NEGATIVE (HIV-1 Ab nonreactive/HIV-2 Ab nonreactive)",
        "0 - 1"=> "HIV-1 INDETERMINATE (HIV-1 Ab indeterminate/HIV-2 Ab nonreactive)",
        "0 - 2"=> "HIV-1 INDETERMINATE (HIV-1 Ab indeterminate/HIV-2 Ab nonreactive)",
        "0 - 4"=> "HIV-1 INDETERMINATE (HIV-1 Ab indeterminate/HIV-2 Ab nonreactive)",
        "0 - 5"=> "HIV-1 INDETERMINATE (HIV-1 Ab indeterminate/HIV-2 Ab nonreactive)",
        "0 - 8"=> "HIV-1 INDETERMINATE (HIV-1 Ab indeterminate/HIV-2 Ab nonreactive)",
        "0 - 10"=> "HIV-1 POSITIVE (HIV-1 Ab reactive/HIV-2 Ab nonreactive)",
        "0 - 11"=> "HIV-1 POSITIVE (HIV-1 Ab reactive/HIV-2 Ab nonreactive)",
        "0 - 12"=> "HIV-1 POSITIVE (HIV-1 Ab reactive/HIV-2 Ab nonreactive)",
        "0 - 13"=> "HIV-1 POSITIVE (HIV-1 Ab reactive/HIV-2 Ab nonreactive)",
        "0 - 14"=> "HIV-1 POSITIVE (HIV-1 Ab reactive/HIV-2 Ab nonreactive)",
        "0 - 15"=> "HIV-1 POSITIVE (HIV-1 Ab reactive/HIV-2 Ab nonreactive)",
        "1 - 11"=> "HIV-1 POSITIVE (HIV-1 Ab reactive/HIV-2 Ab indeterminate)",
        "1 - 14"=> "HIV-1 POSITIVE (HIV-1 Ab reactive/HIV-2 Ab indeterminate)",
        "1 - 15"=> "HIV-1 POSITIVE (HIV-1 Ab reactive/HIV-2 Ab indeterminate)",
        "2 - 0"=> "HIV-2 INDETERMINATE (HIV-1 Ab nonreactive/HIV-2 Ab indeterminate)",
        "2 - 1"=> "HIV INDETERMINATE (HIV-1 Ab indeterminate/HIV-2 Ab indeterminate)",
        "2 - 4"=> "HIV INDETERMINATE (HIV-1 Ab indeterminate/HIV-2 Ab indeterminate)",
        "2 - 8"=> "HIV INDETERMINATE (HIV-1 Ab indeterminate/HIV-2 Ab indeterminate)",
        "2 - 9"=> "HIV-1 POSITIVE (HIV-1 Ab reactive/HIV-2 Ab indeterminate)",
        "2 - 10"=> "HIV-1 POSITIVE (HIV-1 Ab reactive/HIV-2 Ab indeterminate)",
        "2 - 11"=> "HIV-1 POSITIVE (HIV-1 Ab reactive/HIV-2 Ab indeterminate)",
        "2 - 12"=> "HIV-1 POSITIVE (HIV-1 Ab reactive/HIV-2 Ab indeterminate)",
        "2 - 14"=> "HIV-1 POSITIVE (HIV-1 Ab reactive/HIV-2 Ab indeterminate)",
        "2 - 15"=> "HIV-1 POSITIVE (HIV-1 Ab reactive/HIV-2 Ab indeterminate)",
        "3 - 0"=> "HIV-2 POSITIVE (HIV-1 Ab nonreactive/HIV-2 Ab reactive)",
        "3 - 4"=> "HIV-2 POSITIVE (HIV-1 Ab indeterminate/HIV-2 Ab reactive)",
        "3 - 5"=> "HIV-2 POSITIVE (HIV-1 Ab indeterminate/HIV-2 Ab reactive)",
        "3 - 9"=> "HIV-2 POSITIVE with HIV-1 cross-reactivity (HIV-1 Ab reactive (cross-reactivity)/HIV-2 Ab reactive)",
        "3 - 10"=> "HIV-2 POSITIVE with HIV-1 cross-reactivity (HIV-1 Ab reactive (cross-reactivity)/HIV-2 Ab reactive)",
        "3 - 12"=> "HIV-2 POSITIVE with HIV-1 cross-reactivity (HIV-1 Ab reactive (cross-reactivity)/HIV-2 Ab reactive)",
        "3 - 13"=> "HIV-2 POSITIVE with HIV-1 cross-reactivity (HIV-1 Ab reactive (cross-reactivity)/HIV-2 Ab reactive)",
        "3 - 14"=> "HIV-2 POSITIVE with HIV-1 cross-reactivity (HIV-1 Ab reactive (cross-reactivity)/HIV-2 Ab reactive)",
        "3 - 15"=> "HIV POSITIVE Untypable (HIV-1 Ab reactive/HIV-2 Ab reactive)"
    ];

    // Get the result based on summary code
    $result = $result_lookup[$summary_code] ?? "Result not found in the lookup";
    Log::info("Result from Lookup", ['result' => $result]);
    $assayFieldMapping = [];

    foreach($keys as $key) {
        $pin = explode("_", $key);
        if (count($pin) < 2) {
            Log::warning("Unexpected key format", ["key" => $key]);
            continue; // Skip this iteration if the format is not as expected
        }
        $assayFieldMapping[$pin[1]][$pin[0]] = $request->get($pin[0] . "_" . $pin[1]);
    }
    foreach ($assayFieldMapping as $assaySampleID => $assayFields) {

    }

    foreach ($keys as $key) {
        $pin = explode("_", $key);

        // Check if $pin has at least 2 elements
        if (count($pin) < 2) {
            Log::warning("Unexpected key format", ["key" => $key]);
            continue; // Skip this iteration if the format is not as expected
        }

        $inputValue = $request->get($pin[0] . "_" . $pin[1]);

        // Find the record in the database
        $x = Geenius::where('assaySampleID', $pin[1])
            ->where('rdt_type', $rdtType)
            ->where('lotNumber', $lotNumber)->first();

        if ($x) {



            // Update the relevant property based on the user input
            $x->{$pin[0]} = $inputValue; // Assuming the property name corresponds to $pin[0]
            $x->rdt_image = $filePath; // Update image path if applicable
            // Save calculated values
            $x->H1_Code = $H1_Code;
            $x->H2_code = $H2_code;
            $x->summary_code = $summary_code;
            $x->result = $result; // Assuming there's a 'result' column in your table
            $x->save(); // Save the changes to the database
        } else {
            Log::warning("No record found for assaySampleID", ["assaySampleID" => $pin[1]]);
        }
    }

    return redirect()->route('rdt')->with('img_path', $filePath);
}
