jQuery(document).ready(function ($) {
  // tratar o clique do botão Like
  $('.like-button').on('click', function () {
    var postID = $(this).data('post-id');
    var nonce = $(this).data('nonce');
    var voteAction = 'like';

      // Verificar se o usuário já votou neste post
      var votedPosts = localStorage.getItem('like_plugin_voted_posts') || '[]';
      votedPosts = JSON.parse(votedPosts);

      if (votedPosts.indexOf(postID) !== -1) {
          // O usuário já votou neste post, então ignore o clique
          return;
      }

      // Enviar o voto
      $.ajax({
          url: like_plugin_ajax_object.ajax_url,
          type: 'POST',
          dataType: 'json', 
          data: {
              action: 'like_plugin_vote',
              post_id: postID,
              vote_action: voteAction,
              nonce: nonce,
          },
          success: function (response) {
              // Atualizar a contagem de votos 
              if (response.success) {
                  $('.like-count[data-post-id="' + postID + '"]').text(response.data.likes);
                  $('.dislike-count[data-post-id="' + postID + '"]').text(response.data.dislikes);

                  // Armazenar o voto no localStorage para evitar votar novamente neste post
                  votedPosts.push(postID);
                  localStorage.setItem('like_plugin_voted_posts', JSON.stringify(votedPosts));
              }
          },
          error: function (xhr, status, error) {
              console.log(xhr.responseText);
          },
      });
  });

  // tratar o clique do botão Dislike
  $('.dislike-button').on('click', function () {
      var postID = $(this).data('post-id');
      var nonce = $(this).data('nonce');
      var voteAction = 'dislike';

      // Verificar se o usuário já votou neste post 
      var votedPosts = localStorage.getItem('like_plugin_voted_posts') || '[]';
      votedPosts = JSON.parse(votedPosts);

      if (votedPosts.indexOf(postID) !== -1) {
          // O usuário já votou neste post, então ignore o clique
          return;
      }

      // Enviar o voto 
      $.ajax({
          url: like_plugin_ajax_object.ajax_url,
          type: 'POST',
          dataType: 'json',
          data: {
              action: 'like_plugin_vote',
              post_id: postID,
              vote_action: voteAction,
              nonce: nonce,
          },
          success: function (response) {
              // Atualizar a contagem de votos 
              if (response.success) {
                  $('.like-count[data-post-id="' + postID + '"]').text(response.data.likes);
                  $('.dislike-count[data-post-id="' + postID + '"]').text(response.data.dislikes);

                  // Armazenar o voto no localStorage para evitar votar novamente neste post
                  votedPosts.push(postID);
                  localStorage.setItem('like_plugin_voted_posts', JSON.stringify(votedPosts));
              }
          },
          error: function (xhr, status, error) {
              console.log(xhr.responseText);
          },
      });
  });
});