<?php 
/**
 * Classe para registros de usuários
 *
 * @package HamsahMVC
 * @since 0.1
 */
 
class MatrixAdministradoresModel
{
 
	/**
	 * $form_data
	 *
	 * Os dados do formulário de envio.
	 *
	 * @access public
	 */	
	public $form_data;
 
	/**
	 * $form_msg
	 *
	 * As mensagens de feedback para o usuário.
	 *
	 * @access public
	 */	
	public $form_msg;
 
	/**
	 * $db
	 *
	 * O objeto da nossa conexão PDO
	 *
	 * @access public
	 */
	public $db;
 
	/**
	 * Construtor
	 * 
	 * Carrega  o DB.
	 *
	 * @since 0.1
	 * @access public
	 */
	public function __construct( $db = false ) {
                // Carrega o BD
		$this->db = $db;
	}
 
	/**
	 * Valida o formulário de envio
	 * 
	 * Este método pode inserir ou atualizar dados dependendo do campo de
	 * usuário.
	 *
	 * @since 0.1
	 * @access public
	 */
	public function validate_register_form () {
	
		// Configura os dados do formulário
		$this->form_data = array();
		
		// Verifica se algo foi postado
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] && ! empty ( $_POST ) ) {
		
			// Faz o loop dos dados do post
			foreach ( $_POST as $key => $value ) {
			
				// Configura os dados do post para a propriedade $form_data
				$this->form_data[$key] = $value;
				
				if($key != 'id'){
					// Nós não permitiremos nenhum campos em branco exceto id
					if ( empty( $value ) ) {
						
						// Configura a mensagem
						$this->form_msg = '<p class="form_error">Existem campos vazios. Os dados não serão enviados.</p>';
						
						// Termina
						return;
						
					}	
				}			
			
			}
		
		} else {
		
			// Termina se nada foi enviado
			return;
			
		}
		
		// Verifica se a propriedade $form_data foi preenchida
		if( empty( $this->form_data ) ) {
			return;
		}
		
		// Verifica se o administrador existe
		$db_check_user = $this->db->query (
			'SELECT * FROM `admins` WHERE `id` = ? OR `nome` = ?', 
			array( 
				chk_array( $this->form_data, 'id'),
				chk_array( $this->form_data, 'nome'),			
			) 
		);
		
		// Verifica se a consulta foi realizada com sucesso
		if ( ! $db_check_user ) {
			$this->form_msg = '<p class="form_error">Erro Interno.</p>';
			return;
		}
		
		// Obtém os dados da base de dados MySQL
		$fetch_user = $db_check_user->fetch();
		
		// Configura o ID do usuário
		$user_id = $fetch_user['id'];
		
		// Precisaremos de uma instância da classe Phpass
		// veja http://www.openwall.com/phpass/
		$password_hash = new PasswordHash(8, FALSE);
		
		// Cria o hash da senha
		$password = $password_hash->HashPassword( $this->form_data['senha'] );
		
		// Verifica se as permissões tem algum valor inválido: 
		// 0 a 9, A a Z e , . - _
		if ( preg_match( '/[^0-9A-Za-z,.-_s ]/is', $this->form_data['permissions'] ) ) {
			$this->form_msg = '<p class="form_error">Use apenas letras, números e vírgulas para as permissões.</p>';
			return;
		}		
		
		// Faz um trim nas permissões
		$permissions = array_map('trim', explode(',', $this->form_data['permissions']));
		
		// Remove permissões duplicadas
		$permissions = array_unique( $permissions );
		
		// Remove valores em branco
		$permissions = array_filter( $permissions );
		
		// Serializa as permissões
		$permissions = serialize( $permissions );
		
		$date= new DateTime(null,new DateTimeZone(APP_TIMEZONE));
		// Se o ID do usuário não estiver vazio, atualiza os dados
		if ( ! empty( $user_id ) ) {
 
			$query = $this->db->update('admins', 'id', $user_id, array(
				'senha' => $password, 
				'nome' => chk_array( $this->form_data, 'nome'), 
				'telefone' => chk_array( $this->form_data, 'telefone'),
				'session_id' => md5(time()), 
				'permissions' => $permissions,
			));
			
			// Verifica se a consulta está OK e configura a mensagem
			if ( ! $query ) {
				$this->form_msg = '<p class="form_error">Erro Interno. Os dados não serão enviados.</p>';
				
				// Termina
				return;
			} else {
				$this->form_msg = '<p class="form_success">Administrador atualizado com sucesso.</p>';
				
				// Termina
				return;
			}
		// Se o ID do usuário estiver vazio, insere os dados
		} else {
		
			// Executa a consulta 
			$query = $this->db->insert('admins', array(
				'nome' => chk_array( $this->form_data, 'nome'),
				'telefone' => chk_array( $this->form_data, 'telefone'), 
				'senha' => $password,  
				'session_id' => md5(time()), 
				'permissions' => $permissions, 
				'data_inclusao' => $date->format('Y-m-d H:i:s'),
			));
			
			// Verifica se a consulta está OK e configura a mensagem
			if ( ! $query ) {
				$this->form_msg = '<p class="form_error">Erro Interno. Os dados não serão enviados.</p>';
				
				// Termina
				return;
			} else {
				$this->form_msg = '<p class="form_success">Administrador registrado com sucesso.</p>';
				
				// Termina
				return;
			}
		}
	} // validate_register_form
	
	/**
	 * Obtém os dados do formulário
	 * 
	 * Obtém os dados para usuários registrados
	 *
	 * @since 0.1
	 * @access public
	 */
	public function get_register_form ( $user_id = false ) {
	
		// O ID de usuário que vamos pesquisar
		$s_user_id = false;
		
		// Verifica se você enviou algum ID para o método
		if ( ! empty( $user_id ) ) {
			$s_user_id = (int)$user_id;
		}
		
		// Verifica se existe um ID de usuário
		if ( empty( $s_user_id ) ) {
			return;
		}
		
		// Verifica na base de dados
		$query = $this->db->query('SELECT * FROM `admins` WHERE `id` = ?', array( $s_user_id ));
		
		// Verifica a consulta
		if ( ! $query ) {
			$this->form_msg = '<p class="form_error">Administrador não existe</p>';
			return;
		}
		
		// Obtém os dados da consulta
		$fetch_userdata = $query->fetch();
		
		// Verifica se os dados da consulta estão vazios
		if ( empty( $fetch_userdata ) ) {
			$this->form_msg = '<p class="form_error">Administrador não existe.</p>';
			return;
		}
		
		// Configura os dados do formulário
		foreach ( $fetch_userdata as $key => $value ) {
			$this->form_data[$key] = $value;
		}
		
		// Por questões de segurança, a senha só poderá ser atualizada
		$this->form_data['senha'] = null;
		
		// Remove a serialização das permissões
		$this->form_data['permissions'] = unserialize($this->form_data['permissions']);
		
		// Separa as permissões por vírgula
		$this->form_data['permissions'] = implode(',', $this->form_data['permissions']);
	} // get_register_form
	
	/**
	 * Apaga usuários
	 * 
	 * @since 0.1
	 * @access public
	 */
	public function del_user ( $parametros = array() ) {
 
		// O ID do usuário
		$user_id = null;
		
		// Verifica se existe o parâmetro "del" na URL
		if ( chk_array( $parametros, 0 ) == 'del' ) {
 
			// Mostra uma mensagem de confirmação
			echo '<p class="alert">Tem certeza que deseja apagar este valor?</p>';
			echo '<p><a href="' . $_SERVER['REQUEST_URI'] . '/confirma">Sim</a> | 
			<a href="' . HOME_URL . '/matrix-administradores/">Não</a> </p>';
			
			// Verifica se o valor do parâmetro é um número
			if ( 
				is_numeric( chk_array( $parametros, 1 ) )
				&& chk_array( $parametros, 2 ) == 'confirma' 
			) {
				// Configura o ID do usuário a ser apagado
				$user_id = chk_array( $parametros, 1 );
			}
		}
		
		// Verifica se o ID não está vazio
		if ( !empty( $user_id ) ) {
		
			// O ID precisa ser inteiro
			$user_id = (int)$user_id;
			
			// Deleta o usuário
			$query = $this->db->delete('admins', 'id', $user_id);
			
			// Redireciona para a página de registros
			echo '<meta http-equiv="Refresh" content="0; url=' . HOME_URL . '/matrix-administradores/">';
			echo '<script type="text/javascript">window.location.href = "' . HOME_URL . '/matrix-administradores/";</script>';
			return;
		}
	} // del_user
	
	/**
	 * Obtém a lista de usuários
	 * 
	 * @since 0.1
	 * @access public
	 */
	public function get_user_list() {
	
		// Simplesmente seleciona os dados na base de dados 
		$query = $this->db->query('SELECT * FROM `admins` ORDER BY id DESC');
		
		// Verifica se a consulta está OK
		if ( ! $query ) {
			return array();
		}
		// Preenche a tabela com os dados do usuário
		return $query->fetchAll();
	} // get_user_list
}
?>