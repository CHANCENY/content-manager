(function ($){

    $(document).ready(function(){

        let optionsWrapper = $('.option-wrapper');
        if (optionsWrapper) {
            optionsWrapper.find('.add-option').on('click', function(e){
                const collections = optionsWrapper.find('#option-collection');
                const clone = collections.find('.form-group').first().clone();
                clone.find('input').val('');
                collections.append(clone);
                clone.find('a').removeClass('d-none')
                clone.find('a').on('click', function(e){
                    e.preventDefault();
                    $(this).parent().remove();
                })

            })
        }

    })

})(jQuery)