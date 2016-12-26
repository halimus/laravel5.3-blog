<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Yajra\Datatables\Facades\Datatables;


class SurveyController extends Controller {

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() {
        $title = 'Admin | Survey';
        return view('backend.survey.list', compact('title'));
    }
    
    /*
     * 
     */
    public function getSurvey() {
        $items = DB::table('survey')->select(['survey_id', 'question', 'active', 'created_at', 'updated_at'])->orderBy('created_at','desc');
        return Datatables::of($items)
            ->addColumn('action', function ($item) {
                $url_edit = url("/admin/survey/edit/$item->survey_id");
                return '<a href="'.$url_edit.'" class="btn btn-xs btn-primary edit_tag">Edit</a>
                        <a href="#" route="survey" rel="'.$item->survey_id.'" class="btn btn-xs btn-danger dt-delete">Delete</a>';
            })
            ->editColumn('active',
                '@if($active==1) <span class="label label-success">YES</span> @else <span class="label label-danger">NO</span> @endif'
            )
            ->make(true);    
    }
    

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create() {
        $title = 'Admin | Survey';
        return view('backend.survey.add', compact('title'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) {
        
        $rules = $this->rules();
        $messages = $this->messages();
        $validator = Validator::make($request->all(), $rules, $messages);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        else{
            
            $slug = str_slug($request['question'], '-');
             
            $survey = DB::table('survey')->insert([
                'question' => $request['question'],
                'slug' => $slug,
                'active' => $request['active'],
            ]);
            
            $survey_id =  DB::getPdo()->lastInsertId();
            
            $response = DB::table('response')->insert([
                ['response_text' => $request['response1'], 'survey_id' => $survey_id],
                ['response_text' => $request['response2'], 'survey_id' => $survey_id]
            ]);

            if($request['response']){
                foreach($request['response'] as $value){
                    if(!empty($value)){
                        DB::table('response')->insert([
                            ['response_text' => $value, 'survey_id' => $survey_id]
                        ]);
                    }
                }
            }

            if (!$response) {
                throw new Exception('Error in saving data!');
            } 
            else {
                Session::flash('notif_type', 'success');
                Session::flash('notif', 'Survey has been created successfully!');
            }
            
            return redirect("admin/survey/add/");
            
           // \App\Helpers::print_r($_POST);
            //exit;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) {
        
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        $title = 'Admin | Survey';
        return view('backend.survey.edit', compact('title'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) {
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id) {
        $survey = DB::table('survey')->where('survey_id', '=', $id);
        if($survey){
            $survey->delete();
            return response()->json([
                'type' => 'success',
                'msg'  => 'Survey has been deleted!'
            ]);
        }
    }
    
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    private function rules() {
        $rules = [
            'question' => 'required|min:5',
            'response1' => 'required',
            'response2' => 'required',
        ];
        
        return $rules;
    }
    
    /*
     * 
     */
    public function messages() {
        return [
            'question.required' => 'The question for the survey is required.',
            'response1.required' => 'The response1 is required.',
            'response2.required' => 'The response2 is required.'
        ];
    }

}
