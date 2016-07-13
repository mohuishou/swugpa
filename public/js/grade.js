
$(document).ready(function () {
    var myDate=new Date();
    var year=myDate.getFullYear();
    var mo=myDate.getMonth();
    var term;
    if(mo<9) {
        year-=1;
        if(mo>3){
            term=2
        }else {
            term=1;
        }
    }else {
        term=1;
    }
    getGrade(year,term);
});

$('#grade').on('click', 'table tbody tr', function(e) {
    $(this).toggleClass('am-active choose');
});

/**
 * 选择所有课程
 **/
function chooseAll() {
    $('table tbody tr').removeClass('am-active choose');
    $('table tbody tr').addClass('am-active choose');
};

/**
 * 选择所有必修课程
 **/
function chooseReq() {
    $('table tbody tr').removeClass('am-active choose');
    $('.type-1').addClass('am-active choose');
}

/**
 * 计算已选择的成绩/绩点
 **/
function calculation() {
    var sum_xf=0;
    var sum_gpa=0;
    var sum_grade=0;
    var sum_count=0;
    $('.choose').each(function () {
        var s_gpa=Number($(this).find('.gpa').attr('val'));
        var s_grade=Number($(this).find('.grade').attr('val'));
        var s_xf=Number($(this).find('.credit').attr('val'));
        sum_gpa+=s_gpa*s_xf;
        sum_grade+=s_grade*s_xf;
        sum_xf+=s_xf;
        sum_count++;
    });
    var avg_gpa=sum_gpa/sum_xf;
    var avg_grade=sum_grade/sum_xf;

    //保留小数点后两位
    avg_gpa=avg_gpa.toFixed(2);
    avg_grade=avg_grade.toFixed(2);

    $('.cal-choose').text(sum_count);
    $('.cal-gpa').text(avg_gpa);
    $('.cal-grade').text(avg_grade);

    $("#cal").modal('open');

    console.log('平均绩点：'+avg_gpa);
    console.log('平均成绩：'+avg_grade);
}


var count=0;

/**
 * 获取成绩
 * @param y 年份
 * @param t 学期
 */
function getGrade(y,t) {
    var pTerm=0;
    if(t==1){
        pTerm=3;
    }else if(t==2){
        pTerm=12;
    }

    var param={
        "year":y,
        "term":pTerm
    }
    $.ajax({
        type:"POST",
        url:'./grade.php',
        data:param,
        success:function (data) {
            data=JSON.parse(data);
            if(data.status==200){
                var str_grade='';
                for (var i=0;i<data.grade.length;i++){
                    str_grade+= '                   <tr class="type-'+data.grade[i].type+'">'+
                        '                        <td class="class_name">'+data.grade[i].class_name+'</td>'+
                        '                        <td class="grade" val="'+data.grade[i].grade_val+'">'+data.grade[i].grade+'</td>'+
                        '                        <td class="gpa" val="'+data.grade[i].gpa+'" >'+data.grade[i].gpa+'</td>'+
                        '                        <td class="credit" val="'+data.grade[i].credit+'"   >'+data.grade[i].credit+'</td>'+
                        '                        <td>'+data.grade[i].type_name+'</td>'+
                        '                    </tr>';
                }

                var str='<div class="am-panel am-panel-secondary ">'+
                    '            <div class="am-panel-hd">'+
                    '                <h3 class="am-panel-title am-text-center">'+data.time+'第'+data.term+'学期</h3>'+
                    '            </div>'+
                    '            <div class="am-panel-bd">'+
                    '                <div class="am-u-sm-6">'+
                    '                    <p>全部绩点：'+data.avg.all.gpa+'</p>'+
                    '                </div>'+
                    '                <div class="am-u-sm-6">'+
                    '                    <p>全部平均分：'+data.avg.all.grade+'</p>'+
                    '                </div>'+
                    '                <div class="am-u-sm-6">'+
                    '                    <p>必修绩点：'+data.avg.require.gpa+'</p>'+
                    '                </div>'+
                    '                <div class="am-u-sm-6">'+
                    '                    <p>必修平均分：'+data.avg.require.grade+'</p>'+
                    '                </div>'+
                    '            </div>'+
                    '            <table class="am-table am-table-bordered am-table-radius am-table-hover am-table-centered am-table-striped">'+
                    '                <thead>'+
                    '                    <tr>'+
                    '                        <th>课程名</th>'+
                    '                        <th>分数</th>'+
                    '                        <th>绩点</th>'+
                    '                        <th>学分</th>'+
                    '                        <th>属性</th>'+
                    '                    </tr>'+
                    '                </thead>'+
                    '                <tbody>'+str_grade+
                    '                </tbody>'+
                    '            </table>'+
                    '        </div>';
                $('#grade').append(str);

                       if(y>data.nj){
                           if(t==2){
                               t=t-1;
                               getGrade(y,t);
                           }else {
                               y=y-1;
                               t=2;
                               getGrade(y,t);
                           }
                       }else if(y==data.nj&&t==2){
                           t=t-1;
                           getGrade(y,t);
                       }else {
                           $('#loading').hide();
                       }

            }else if(data.status==20001){
                swal('提示',"尚未登录<br />", 'warning');
                setTimeout(function () {
                    location.href="./index.html";
                },1000);
            }else if(data.status==20002){
                swal('提示','参数错误', 'warning');
                $('#loading').hide();
            }else {
                if(count==0){
                    if(t==1){
                        y=y-1;
                        getGrade(y,t);
                    }else {
                        t-=1;
                        getGrade(y,t);
                    }
                }else {
                    swal('提示',"获取成绩失败<br />"+data.msg, 'warning');
                    $('#loading').hide();
                }

            }
            // console.log(data);
        },
        error:function (e) {
            $('#loading').hide();
            swal('Oops...', '服务器错误', 'error');
        }
    });
    count++;
}
