;(function($) {
    var SubTask = {
        init: function() {

            if( $('.cpm-complete').first().is( ':checked' ) === false ) {
                $('.cpm-task-complete .cpmst-complete').attr('checked', 'checked').attr('disabled', false);
                $('.cpm-task-uncomplete .cpmst-uncomplete').removeAttr('checked').attr('disabled', false);
            }

            $('ul.cpmst-todolist').on('click', 'a.add-task', this.showNewTodoForm);
            $('ul.cpmst-todolist').on('click', '.cpm-todos-new a.todo-cancel', this.hideNewTodoForm);
            $('.cpm-single-task').on('submit', '.cpm-todo-form form.cpmst-sub-task', this.submitNewTodo);
            $('.cpm-single-task').on('click', 'input[type=checkbox].cpmst-uncomplete', this.markDone);
            $('.cpm-single-task').on('click', 'input[type=checkbox].cpmst-complete', this.markUnDone);
            $('.cpm-single-task').on('submit', '.cpmst-task-edit-form form.cpmst-sub-task', this.updateTodo);
            $('.cpm-single-task').on('click', '.cpmst-todo-action a.cpmst-todo-edit', this.toggleEditTodo);
            $('.cpm-single-task').on('click', '.cpmst-task-edit-form a.todo-cancel', this.toggleEditTodo);
            $('.cpm-single-task').on('click', 'a.cpmst-todo-delete', this.deleteTodo);


            $(document).on('cpm.markUnDone.after', function( event, content ) { 

                $('ul.cpmst-todolist').on('click', 'a.add-task', SubTask.showNewTodoForm);
                //$('ul.cpmst-todolist').on('submit', '.cpm-todo-form form.cpmst-sub-task', SubTask.submitNewTodo);
                $('ul.cpmst-todolist').on('click', '.cpm-todos-new a.todo-cancel', SubTask.hideNewTodoForm);
                //$('.cpm-single-task').on('submit', '.cpmst-task-edit-form form.cpmst-sub-task', SubTask.updateTodo);
                //$('.cpm-single-task').on('click', '.cpmst-todo-action a.cpmst-todo-edit', SubTask.toggleEditTodo);
                $('.cpm-single-task').on('click', '.cpmst-task-edit-form a.todo-cancel', SubTask.toggleEditTodo);
                //$('.cpm-single-task').on('click', 'a.cpmst-todo-delete', SubTask.deleteTodo);
                $(".datepicker").datepicker();
            });                
        },

        deleteTodo: function (e) {
            e.preventDefault();

            var self = $(this),
                list = self.closest('li'),
                taskListEl = self.closest('article.cpm-todolist'),
                confirmMsg = self.data('confirm'),
                single = self.data('single'),
                data = {
                    list_id: self.data('list_id'),
                    project_id: self.data('project_id'),
                    task_id: self.data('task_id'),
                    action: 'cpmst_task_delete',
                    '_wpnonce': CPMST_var._nonce
                };

            if( confirm(confirmMsg) ) {

                self.addClass('cpm-icon-delete-spinner');
                self.closest('.cpmst-todo-action').show();

                $.post(CPMST_var.ajaxurl, data, function (res) {
                    res = JSON.parse(res);

                    if(res.success) {
                        if(single !== '') {
                            location.href = res.list_url;
                        } else {
                            list.fadeOut(function() {
                                $(this).remove();
                            });

                            //update progress
                            taskListEl.find('h3 .cpm-right').html(res.progress);
                        }
                    }
                });
            }
        },


        toggleEditTodo: function (e) {
            e.preventDefault();

            var wrap = $(this).closest('.cpm-todo-wrap');

            wrap.find('.cpm-todo-content').toggle();
            wrap.find('.cpmst-task-edit-form').slideToggle();
        },

        markUnDone: function () {

            var self = $(this);
            self.attr( 'disabled', true );
            self.siblings('.cpm-spinner').show();
            
            var list = self.closest('li'),
                taskListEl = self.closest('article.cpm-todolist'),
                singleWrap = self.closest('.cpm-single-task'),
                data = {
                    task_id: self.val(),
                    project_id: self.data('project'),
                    list_id: self.data('list'),
                    single: self.data('single'),
                    action: 'cpmst_task_open',
                    '_wpnonce': CPMST_var._nonce
                };

            $(document).trigger('cpm.markUnDone.before', [self]);
            $.post(CPMST_var.ajaxurl, data, function (res) {
                res = JSON.parse(res);

                if(res.success === true ) {

                    if(list.length) {
                        var currentList = list.parent().siblings('.cpm-todos');

                        currentList.append('<li>' + res.content + '</li>');
                        list.remove();

                        //update progress
                        taskListEl.find('h3 .cpm-right').html(res.progress);

                    } else if(singleWrap.length) {
                        singleWrap.html(res.content);
                    }
                }

                $(document).trigger('cpm.markUnDone.after', [res,self]);
            });
        },


        markDone: function () {

            var self = $(this);
            self.attr( 'disabled', true );
            self.siblings('.cpm-spinner').show();

            var list = self.closest('li'),
                taskListEl = self.closest('article.cpm-todolist'),
                singleWrap = self.closest('.cpm-single-task'),
                data = {
                    task_id: self.val(),
                    project_id: self.data('project'),
                    list_id: self.data('list'),
                    single: self.data('single'),
                    action: 'cpmst_task_complete',
                    '_wpnonce': CPMST_var._nonce
                };

            $(document).trigger('cpm.markDone.before', [self]);

            $.post(CPMST_var.ajaxurl, data, function (res) {
                res = JSON.parse(res);
                $(document).trigger('cpm.markDone.after', [res,self]);
                if(res.success === true ) {

                    if(list.length) {
                        var completeList = list.parent().siblings('.cpm-todo-completed');
                        completeList.append('<li>' + res.content + '</li>');

                        list.remove();

                        //update progress
                        taskListEl.find('h3 .cpm-right').html(res.progress);

                    } else if(singleWrap.length) {
                        singleWrap.html(res.content);
                    }
                    $(document).trigger('cpm.markDone.after');
                }
            });
        },

        showNewTodoForm: function (e) {
            e.preventDefault();

            var self = $(this),
                next = self.parent().next();

            self.closest('li').addClass('cpm-hide');
            next.removeClass('cpm-hide');
        },
        submitNewTodo: function (e) {
            e.preventDefault();

            var self = $(this),
                this_btn = self.find('input[name=submit_todo]'),
                spinner = self.find('.cpm-new-task-spinner');

            var data = self.serialize(),
                taskListEl = self.closest('article.cpm-todolist'),
                content = $.trim(self.find('.todo_content').val());

            $(document).trigger('cpm.submitNewTodo.before',[self]);
            
            if(content !== '') {

                this_btn.attr( 'disabled', true );
                spinner.show();
                $.post(CPMST_var.ajaxurl, data, function (res) {

                    this_btn.attr( 'disabled', false );
                    spinner.hide();

                    res = JSON.parse(res);

                    if(res.success === true) {
                        var currentList = self.closest('ul.cpm-todos-new').siblings('.cpm-todos');
                        currentList.append( '<li>' + res.content + '</li>' );

                        //clear the form
                        self.find('textarea, input[type=text]').val('');
                        self.find('select').val('-1');

                        //update progress
                        taskListEl.find('h3 .cpm-right').html(res.progress);
                        
                    } else {
                        alert('something went wrong!');
                    }

                    $(document).trigger('cpm.submitNewTodo.after',[res,self]);
                });

            } else {
                alert('type something');
            }
        },

        updateTodo: function (e) {
            e.preventDefault();
            var self = $(this),
                this_btn = self.find('input[name=submit_todo]'),
                spinner = self.find('.cpm-new-task-spinner');

            var data = self.serialize(),
                list = self.closest('li'),
                singleWrap = self.closest('.cpm-single-task'),
                content = $.trim(self.find('.todo_content').val());

            $(document).trigger('cpm.updateTodo.before',[self]);
            
            if(content !== '') {
                this_btn.attr( 'disabled', true );
                spinner.show();

                $.post(CPMST_var.ajaxurl, data, function (res) {
                    this_btn.attr( 'disabled', false );
                    spinner.hide();

                    res = JSON.parse(res);
                    
                    if(res.success === true) {
                        if(list.length) {
                            list.html(res.content); //update in task list
                        } else if(singleWrap.length) {
                            singleWrap.html(res.content); //update in single task
                        }
                        $(document).trigger('cpm.time.tracker');
                        $('.datepicker').datepicker();
                    } else {
                        alert('something went wrong!');
                    }

                    $(document).trigger('cpm.updateTodo.after',[res,self]);
                });
            } else {
                alert('type something');
            }
        },


        hideNewTodoForm: function (e) {
            e.preventDefault();

            var self = $(this),
                list = self.closest('li');

            list.addClass('cpm-hide');
            list.prev().removeClass('cpm-hide');
        },


    }
    $(function() {
        SubTask.init();

    })
    
})(jQuery);