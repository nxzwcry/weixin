<?php
namespace App\Http\Controllers;
use Carbon\Carbon;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Student;
use App\Lesson;
use App\Course;
use App\Recharge;
use App\Courseware;
use App\Cteacher;
use App\Place;

class LessonController extends Controller
{
	// 显示增加页面
//	public function index($sid)
//	{
//		$students = Student::where('id' , $sid)
//  		-> get(['id' , 'name' , 'ename'])
//			-> first();
////  	dd($students);
//      return view('admin.clesson' , ['students' => $students]);
//	}
	
	// 显示学生课程信息(管理界面)
	public function info($sid)
	{
		// 学生信息
		$students = Student::where('id' , $sid)
//    		-> get(['id' , 'name' , 'ename'])
			-> first();
		// 固定课程信息
		$courses = Course::where('sid' , $sid)
			-> where(function($query){
				$query -> where( 'edate' , null )
				-> orwhere( 'edate' , '>=' , Carbon::now() -> timestamp );
			})
			-> orderby('dow')
			-> with('cteacher')
    		-> get();
    			
		// 已完成固定课程信息
		$oldcourses = Course::where('sid' , $sid)
			-> where( 'edate' , '<' , Carbon::now() -> timestamp )
			-> orderby('edate' , 'desc' )
			-> with('cteacher')
    		-> get();
			
		// 购课记录
		$recharges = Recharge::where('sid' , $sid)
			-> orderby('created_at' , 'desc' )
    		-> get();
    	
    	// 已上课程列表
    	$lessons = Lesson::where('sid' , $sid)
			-> where('conduct' , 1 )
			-> orderby('date' , 'desc' )
			-> orderby('etime' , 'desc' )
			-> with('cteacher')
    		-> get();
    		
    	// 下节课程	
    	$newlessons = Lesson::where('sid' , $sid)
			-> where('conduct' , 0 )
			-> orderby('date' )
			-> orderby('stime' )
			-> with('cteacher')
    		-> get();
    		
    	$i = 0;
    	$tenlessons = [];
    	foreach ( $lessons as $lesson )
    	{
    		$tenlessons[$i] = $lesson -> type;
    		$i++;
    		if ( $i >= 10 )
    		{
    			break;
    		}
    	}
    	if ( $i < 10 )
    	{
    		for ( $i ; $i < 10 ; $i++ )
    		{
    			$tenlessons[$i] = 'n';	
    		}	
    	}

        return view('admin.lessonsinfo' , 
        ['students' => $students , 
        'courses' => $courses , 
        'oldcourses' => $oldcourses , 
        'recharges' => $recharges , 
        'lessons' => $lessons,
        'newlessons' => $newlessons,
        'tenlessons' => $tenlessons]);
	}
	
	use LessonCreate;
	
	//处理添加单节课程请求
	public function create(Request $request)
	{

//      $this -> validate -> errors() -> add('lerror' , '1');
		$this -> validate($request,[
            'sid' => 'required|numeric|exists:students,id',
            'stime' => 'required',
            'etime' => 'required',
            'date' => 'required|date',
            'cost' => 'required|numeric|max:5',  
            'cost1' => 'required|numeric|max:5',
            'cost2' => 'required|numeric|max:5',         
        ],[
            'required' => '输入不能为空',
            'date.date' => '请按照正确格式输入日期',
        ]);
        
        $lessoninfo = $request -> all();
        $ans = $this -> createlesson( $lessoninfo );        
		
        return redirect('lessonsinfo/' . $request -> sid );
	}
	
	//处理删除单节课程请求
	public function delete(Request $request)
	{
		
//      $this -> validate -> errors() -> add('lerror' , '1');
//		$this -> validate($request,[
//          'sid' => 'required|numeric|exists:students,id',
//          'id' => 'required|numeric|exists:lessons,id',
//      ]);
        
        $lesson = Lesson::find($request -> id);
//      dd($lesson);
//      $lesson -> delete();
		if ( $lesson -> sid == $request -> sid )
		{			
        	if( $this -> deletelesson( $request -> id ) )
        	{        		
        		return redirect('lessonsinfo/' . $request -> sid );
        	} 
		}
		
        return 0;
	}
	
	// 显示文件上传页面
	public function fileupdateindex($lid)
	{
		$lesson = Lesson::find($lid);
		$student = Student::find( $lesson -> sid );
		$cws = Courseware::all();
		if ( $lesson -> cwurl == null )
		{
			$cwid = 0;
		}
		else		
		{
			$cw = Courseware::where( 'url' , $lesson -> cwurl );
			if ( $cw -> first() )
			{
				$cwid = $cw -> first() -> id;
			}
			else{
				$cwid = -1;
			}
		}
//  	dd($students);
        return view('admin.fupdate' , ['lesson' => $lesson , 'student' => $student , 'cws' => $cws , 'cwid' => $cwid , 'url' => '/lesson']);
	}
		
	// 存储视频上传信息
	public function videoupdate(Request $request)
	{		
		$this -> validate($request,[
            'name' => 'required',
            'id' => 'required',
            'vid' => 'required',           
        ],[
            'required' => '输入不能为空',
        ]);

//      dd($request);
		$lesson = Lesson::find( $request -> id );
		$lesson -> name =  $request -> name;
		$lesson -> tname =  $request -> tname;
		$lesson -> vid =  $request -> vid;
		$lesson -> save();
		return $this -> info( $request -> sid );
	}
			
	// 存储文件上传信息
	public function fileupdate(Request $request)
	{
		$lesson = Lesson::find( $request -> id );
		if ( $request -> type == 'gd' )
		{
			if ( $request -> cwid == 0 )
			{
				$lesson -> cwurl =  null;
			}
			else
			{
				$lesson -> cwurl = Courseware::find( $request -> cwid ) -> url;
			}
		}
		else
		{
			$lesson -> cwurl = $request -> cwurl;
		}
		$lesson -> furl = $request -> furl;
		$lesson -> save();
		return $this -> info( $request -> sid );
	}	
	
	
	// 修改课程信息显示页面
	public function changeindex(Request $request)
	{
		$lesson = Lesson::find($request -> id);
		if ($lesson)
		{			
			$cteachers = Cteacher::all();
			$places = Place::all();
//			$request -> flash('type' , 'j');
//			dd($request);
			return view( 'admin.lchange' , [ 'lesson' => $lesson , 'cteachers' => $cteachers , 'places' => $places]);
		}
	}
		
	// 修改课程信息操作
	public function change(Request $request)
	{
		$this -> validate($request,[
            'id' => 'required|numeric|exists:lessons,id',
            'stime' => 'required',
            'etime' => 'required',
            'date' => 'required|date',
            'cost' => 'required|numeric|max:5',  
            'cost1' => 'required|numeric|max:5',
            'cost2' => 'required|numeric|max:5',         
        ],[
            'required' => '输入不能为空',
            'date.date' => '请按照正确格式输入日期',
        ]);

		$lesson = Lesson::find($request -> id);
		$info = $request -> all();
		$etime = Carbon::parse( $request -> date . $request -> etime );
                
        if ( Carbon::now() -> gte( $etime ) )
        {
        	$info['conduct'] = 1;
        }
        else 
        {
        	$info['conduct'] = 0;
        }
		if ( $lesson )
		{
			if ( $lesson -> update($info) )
			{
				return redirect('lessonsinfo/' . $lesson -> sid );
			}
		}
	}
	
}
?>