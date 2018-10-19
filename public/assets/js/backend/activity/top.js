define(['jquery', 'bootstrap', 'backend', 'table', 'form', 'template'], function ($, undefined, Backend, Table, Form, Template) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'activity.top/index',
                    add_url: 'activity/top/add',
                    edit_url: 'activity/top/edit',
                    del_url: 'activity/top/del',
                    multi_url: 'activity/top/multi',
                    table: 'activity_top',
                }
            });

            var table = $("#table");

            // 为表格绑定事件
            Table.api.bindevent(table);

            // 查看视频
            $(document).on('click', ".show-top-video", function (data) {
                var url = $(this).attr('data-url');
                if(!url) return false;
                var msg = $(this).attr('data-title');
                // var width = $(this).attr('data-width');
                // var height = $(this).attr('data-height');
                // var area = [$(window).width() > 800 ? (width?width:'800px') : '95%', $(window).height() > 600 ? (height?height:'600px') : '95%'];
                //var area = ['90%', '100%'];
                var options = {
                    shadeClose: true,
                    shade: [0.3, '#393D49'],
                    //area: area,
                    callback:function(value){
                        // CallBackFun(value.id, value.name);//在回调函数里可以调用你的业务代码实现前端的各种逻辑和效果
                    }
                };
                Fast.api.open(url,msg,options);
            });
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            },
            formatter: {}
        }
    };
    return Controller;
});